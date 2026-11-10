# Part of Odoo. See LICENSE file for full copyright and licensing details.

import os
import re
import sys

from odoo import _, api, fields, models
from odoo.http import request
from odoo.exceptions import ValidationError

from odoo.addons.payment import utils as payment_utils
from odoo.addons.payment.logging import get_payment_logger
from odoo.addons.payment_payvector import const
from odoo.addons.payment_payvector.controllers.main import (
    PayVectorController,
    fetch_gateway_transaction_result,
)
_lib_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), "lib")
if _lib_path not in sys.path:
    sys.path.insert(0, _lib_path)

from thepaymentgateway import (
    TransactionProcessor,
    PaymentFormHelper,
    ThreeDSecureAuthentication,
    ThreeDSecureEnvironment,
    ResultDeliveryMethod,
    TransactionType,
)
from thepaymentgateway.payment_form_helper import TransactionResult
from thepaymentgateway.transaction_processor import NativeSessionHandler
from .payvector_saved_card import is_dummy_or_zero_last4

_logger = get_payment_logger(__name__)


class PaymentTransaction(models.Model):
    _inherit = "payment.transaction"

    payvector_3ds_cross_reference = fields.Char(
        string="PayVector 3DS Cross Reference",
        readonly=True,
    )
    payvector_temp_last_four = fields.Char("Temporary Card Last Four", readonly=True)
    payvector_temp_card_type = fields.Char("Temporary Card Type", readonly=True)
    payvector_temp_expiry_month = fields.Char("Temporary Expiry Month", readonly=True)
    payvector_temp_expiry_year = fields.Char("Temporary Expiry Year", readonly=True)

    @staticmethod
    def _payvector_detect_card_type(card_number):
        if not card_number:
            return "Card"
        num = re.sub(r"\D", "", str(card_number))
        if not num:
            return "Card"
        if num.startswith("4"):
            return "Visa"
        if len(num) >= 2 and 51 <= int(num[:2]) <= 55:
            return "MasterCard"
        if len(num) >= 4 and 2221 <= int(num[:4]) <= 2720:
            return "MasterCard"
        if len(num) >= 2 and num[:2] in ("34", "37"):
            return "Amex"
        if num.startswith("6011") or num.startswith("65") or (len(num) >= 3 and 644 <= int(num[:3]) <= 649):
            return "Discover"
        if (len(num) >= 2 and num[:2] in ("36", "38")) or (len(num) >= 3 and 300 <= int(num[:3]) <= 305):
            return "Diners Club"
        if len(num) >= 4 and 3528 <= int(num[:4]) <= 3589:
            return "JCB"
        if num.startswith("62"):
            return "UnionPay"
        if len(num) >= 4 and num[:4] in ("5018", "5020", "5038", "5893", "6304", "6759", "6761", "6762", "6763"):
            return "Maestro"
        return "Card"

    @staticmethod
    def _payvector_format_card_type(card_type):
        if not card_type:
            return "Card"
        ct = str(card_type).strip()
        ct_upper = ct.upper()
        mapping = {
            "VISA": "Visa",
            "MASTERCARD": "MasterCard",
            "MC": "MasterCard",
            "AMEX": "Amex",
            "AMERICAN EXPRESS": "Amex",
            "DISCOVER": "Discover",
            "DINERS": "Diners Club",
            "DINERS CLUB": "Diners Club",
            "JCB": "JCB",
            "MAESTRO": "Maestro",
            "SOLO": "Solo",
            "DELTA": "Delta",
            "ELECTRON": "Visa Electron",
        }
        return mapping.get(ct_upper, ct.title() if ct.isupper() else ct)

    def _get_specific_rendering_values(self, processing_values):
        res = super()._get_specific_rendering_values(processing_values)
        if self.provider_code != "payvector":
            return res

        provider = self.provider_id
        base_url = provider.get_base_url()

        has_saved_card = False
        saved_card_last_four = ""
        saved_card_type = ""

        if provider.payvector_show_saved_cards and self.partner_id:
            saved_card = self.env["payvector.saved.card"].sudo().search(
                [("partner_id", "=", self.partner_id.id)],
                limit=1,
            )
            if saved_card and saved_card.cross_reference:
                saved_card_last_four = str(saved_card.last_four or "").strip()
                if is_dummy_or_zero_last4(saved_card_last_four):
                    recent_txs = self.env["payment.transaction"].sudo().search(
                        [
                            ("partner_id", "=", self.partner_id.id),
                            ("provider_id", "=", provider.id),
                            ("state", "=", "done"),
                            ("payvector_temp_last_four", "!=", False),
                        ],
                        order="id desc",
                        limit=10,
                    )
                    found_last4 = None
                    for tx in recent_txs:
                        if not is_dummy_or_zero_last4(tx.payvector_temp_last_four):
                            found_last4 = str(tx.payvector_temp_last_four).strip()
                            break
                    if found_last4:
                        saved_card_last_four = found_last4
                        saved_card.sudo().write({"last_four": saved_card_last_four})
                    else:
                        saved_card_last_four = ""

                if saved_card_last_four and not is_dummy_or_zero_last4(saved_card_last_four):
                    has_saved_card = True
                    saved_card_type = self._payvector_format_card_type(saved_card.card_type) or "Card"
                else:
                    has_saved_card = False
                    saved_card_last_four = ""

        current_year = fields.Date.today().year
        years = list(range(current_year, current_year + 15))


        if provider.payvector_integration_mode == "hosted":
            res = self._payvector_get_hosted_rendering_values(
                processing_values, base_url
            )
            res.update({
                "has_saved_card": has_saved_card,
                "saved_card_last_four": saved_card_last_four,
                "saved_card_type": saved_card_type,
                "years": years,
            })
            res["values"] = res.copy()
            return res
        else:
            res = {
                "api_url": base_url + PayVectorController._direct_payment_url,
                "payvector_mode": "direct",
                "reference": self.reference,
                "provider_id": provider.id,
                "has_saved_card": has_saved_card,
                "saved_card_last_four": saved_card_last_four,
                "saved_card_type": saved_card_type,
                "partner_name": self.partner_name or self.partner_id.name or "",
                "years": years,
            }
            res["values"] = res.copy()
            return res

    def _payvector_get_hosted_rendering_values(self, processing_values, base_url):
        provider = self.provider_id

        currency_code = provider._payvector_get_iso_currency_code(self.currency_id)
        if not currency_code:
            raise ValidationError(
                _("Currency %s is not supported by PayVector.", self.currency_id.name)
            )

        minor_amount = provider._payvector_amount_to_minor(
            self.amount, self.currency_id
        )

        country_code = provider._payvector_get_iso_country_code(
            self.partner_id.country_id.code if self.partner_id.country_id else None
        )

        entry_points = provider._payvector_get_entry_points()
        session_handler = NativeSessionHandler()

        processor = TransactionProcessor(
            merchant_id=provider.payvector_merchant_id,
            merchant_password=provider.payvector_password,
            request_gateway_entry_points=entry_points,
            currency_code=currency_code,
            amount=minor_amount,
            order_id=self.reference,
            order_description=f"Order {self.reference}",
            customer_name=self.partner_name or self.partner_id.name or "",
            address1=self.partner_address or self.partner_id.street or "",
            city=self.partner_city or self.partner_id.city or "",
            postcode=self.partner_zip or self.partner_id.zip or "",
            country_code=country_code or "",
            email_address=self.partner_email or self.partner_id.email or "",
            phone_number=self.partner_phone or self.partner_id.phone or "",
            transaction_type=TransactionType.SALE,
        )

        callback_url = base_url + PayVectorController._return_url
        result_delivery = provider.payvector_result_delivery or "POST"
        server_result_url = (
            (base_url + PayVectorController._server_result_url)
            if result_delivery == "SERVER"
            else ""
        )
        payment_form_displays_result = True if result_delivery != "SERVER" else False

        form_data = processor.get_hosted_payment_form(
            callback_url=callback_url,
            server_result_url=server_result_url,
            pre_shared_key=provider.payvector_pre_shared_key or "",
            hash_method=provider.payvector_hash_method or "SHA1",
            result_delivery_method=result_delivery,
            payment_form_displays_result=payment_form_displays_result,
            session_handler=session_handler,
        )

        form_data["api_url"] = const.HOSTED_FORM_URL
        form_data["direct_api_url"] = base_url + PayVectorController._direct_payment_url
        form_data["reference"] = self.reference
        form_data["provider_id"] = provider.id
        form_data["payvector_mode"] = "hosted"
        return form_data

    def _payvector_process_direct_payment(
        self,
        card_number="",
        expiry_month="",
        expiry_year="",
        cv2="",
        cardholder_name=None,
        browser_data=None,
        base_url=None,
        use_saved_card=False,
        cross_reference=None,
    ):
        self = self.with_context(payment_safe_write=True)
        self.ensure_one()
        provider = self.provider_id

        currency_code = provider._payvector_get_iso_currency_code(self.currency_id)
        if not currency_code:
            return {
                "status": "error",
                "message": f"Currency {self.currency_id.name} is not supported.",
            }

        minor_amount = provider._payvector_amount_to_minor(
            self.amount, self.currency_id
        )

        country_code = provider._payvector_get_iso_country_code(
            self.partner_id.country_id.code if self.partner_id.country_id else None
        )

        entry_points = provider._payvector_get_entry_points()
        session_handler = NativeSessionHandler()

        if not base_url:
            from odoo.http import request
            if request and hasattr(request, "httprequest"):
                base_url = request.httprequest.url_root
            else:
                base_url = provider.get_base_url()

        if base_url and base_url.endswith("/"):
            base_url = base_url[:-1]

        callback_3ds_url = f"{base_url}{PayVectorController._3ds_url}?tx_reference={self.reference}"
        challenge_data_url = f"{base_url}{PayVectorController._3ds_data_url}?tx_reference={self.reference}"

        processor = TransactionProcessor(
            merchant_id=provider.payvector_merchant_id,
            merchant_password=provider.payvector_password,
            request_gateway_entry_points=entry_points,
            currency_code=currency_code,
            amount=minor_amount,
            order_id=self.reference,
            order_description=f"Order {self.reference}",
            customer_name=cardholder_name or self.partner_name or self.partner_id.name or "",
            address1=self.partner_address or self.partner_id.street or "",
            city=self.partner_city or self.partner_id.city or "",
            postcode=self.partner_zip or self.partner_id.zip or "",
            country_code=country_code or "",
            email_address=self.partner_email or self.partner_id.email or "",
            phone_number=self.partner_phone or self.partner_id.phone or "",
            ip_address=browser_data.get("ip_address", "127.0.0.1") if browser_data else "127.0.0.1",
            transaction_type=TransactionType.SALE,
            cv2=cv2,
        )

        if browser_data:
            processor.java_enabled = browser_data.get("java_enabled", False)
            processor.java_script_enabled = browser_data.get("javascript_enabled", True)
            processor.screen_width = browser_data.get("screen_width", 1920)
            processor.screen_height = browser_data.get("screen_height", 1080)
            processor.screen_colour_depth = browser_data.get("color_depth", 24)
            processor.timezone_offset = browser_data.get("timezone", 0)
            processor.language = browser_data.get("language", "en-US")

        processor.challenge_notification_url = challenge_data_url
        processor.fingerprint_notification_url = callback_3ds_url

        clean_num = re.sub(r"\D", "", card_number or "")
        clean_last_four = clean_num[-4:] if len(clean_num) >= 4 else ""
        detected_card_type = self._payvector_detect_card_type(clean_num) if clean_num else None

        clean_exp_m = ""
        clean_exp_y = ""
        if expiry_month:
            m_digits = re.sub(r"\D", "", str(expiry_month))
            clean_exp_m = f"0{m_digits}" if len(m_digits) == 1 else m_digits[:2]
        if expiry_year:
            y_digits = re.sub(r"\D", "", str(expiry_year))
            clean_exp_y = y_digits[-2:] if len(y_digits) >= 2 else y_digits

        if use_saved_card and cross_reference:
            result = processor.do_cross_reference_transaction(
                cross_reference=cross_reference,
                three_d_secure_override_policy=False,
                session_handler=session_handler,
            )
        else:
            if not clean_num:
                self._set_error(_("Credit card number is required."))
                return {
                    "status": "error",
                    "message": "Credit card number is required.",
                }
            result = processor.do_card_details_transaction(
                card_number=clean_num,
                expiry_date_month=int(clean_exp_m) if clean_exp_m else None,
                expiry_date_year=int(clean_exp_y) if clean_exp_y else None,
                issue_number=None,
                session_handler=session_handler,
            )

        status_code = result.get_status_code()

        raw_res = ""
        if hasattr(result, "to_transaction_object") and hasattr(result.to_transaction_object, "last_response"):
            raw_res = result.to_transaction_object.last_response

        if status_code == const.STATUS_SUCCESS:
            cross_ref = result.get_cross_reference()
            last_four = result.get_card_last_four() or clean_last_four or ""
            if last_four:
                digits_l4 = re.sub(r"\D", "", str(last_four))
                if len(digits_l4) >= 4:
                    last_four = digits_l4[-4:]
            card_type = self._payvector_format_card_type(
                result.get_card_type() or detected_card_type or "Visa"
            )
            vals_direct = {
                "provider_reference": cross_ref,
                "payvector_temp_card_type": card_type,
                "payvector_temp_expiry_month": str(clean_exp_m) if clean_exp_m else "12",
                "payvector_temp_expiry_year": str(clean_exp_y) if clean_exp_y else "35",
            }
            if last_four and not is_dummy_or_zero_last4(last_four):
                vals_direct["payvector_temp_last_four"] = last_four
            self.sudo().write(vals_direct)

            if last_four and not is_dummy_or_zero_last4(last_four):
                self._payvector_save_customer_card(
                    cross_ref,
                    last_four=last_four,
                    card_type=card_type,
                    expiry_month=clean_exp_m,
                    expiry_year=clean_exp_y,
                )
            self._set_done()
            return {
                "status": "done",
                "cross_reference": result.get_cross_reference(),
                "auth_code": result.get_auth_code(),
                "raw_response": raw_res,
            }

        elif status_code == const.STATUS_3DS_REQUIRED:
            output_data = result.get_three_d_secure_output_data()
            cross_reference = result.get_cross_reference()

            last_four = result.get_card_last_four() or clean_last_four
            card_type = self._payvector_format_card_type(
                result.get_card_type() or detected_card_type
            )

            self.sudo().write({
                "payvector_3ds_cross_reference": cross_reference,
                "payvector_temp_last_four": last_four if (last_four and not is_dummy_or_zero_last4(last_four)) else False,
                "payvector_temp_card_type": card_type or False,
                "payvector_temp_expiry_month": str(clean_exp_m) if clean_exp_m else False,
                "payvector_temp_expiry_year": str(clean_exp_y) if clean_exp_y else False,
            })

            if not output_data:
                self._set_error(
                    _("3D Secure required but gateway provided no authentication data.")
                )
                return {
                    "status": "error",
                    "message": "3D Secure data missing from gateway response.",
                }

            three_d_version = output_data.three_d_secure_version or ""
            is_v2 = three_d_version.startswith("2") or "VERSION_2" in three_d_version.upper()

            tds_data = {
                "cross_reference": cross_reference,
                "three_d_secure_version": three_d_version,
                "is_v2": is_v2,
                "tx_reference": self.reference,
            }

            if is_v2 and output_data.method_url:
                tds_data["type"] = "fingerprint"
                tds_data["acs_url"] = output_data.method_url
                tds_data["target"] = "_self"
                tds_data["params"] = {
                    "threeDSMethodData": output_data.method_data or "",
                }

            elif is_v2 and output_data.creq:
                import base64
                session_data = base64.urlsafe_b64encode(
                    (cross_reference or "").encode("utf-8")
                ).decode("utf-8").rstrip("=")
                
                tds_data["type"] = "challenge_v2"
                tds_data["acs_url"] = output_data.acs_url
                tds_data["target"] = "threeDSecureFrame"
                tds_data["params"] = {
                    "creq": output_data.creq,
                    "threeDSSessionData": session_data,
                }

            elif output_data.pa_req and output_data.acs_url:
                tds_data["type"] = "challenge_v1"
                tds_data["acs_url"] = output_data.acs_url
                tds_data["target"] = "_self"
                tds_data["params"] = {
                    "PaReq": output_data.pa_req,
                    "MD": cross_reference,
                    "TermUrl": callback_3ds_url,
                }

            else:
                self._set_error(
                    _("3D Secure data incomplete — no method_url, creq, or pa_req received.")
                )
                return {
                    "status": "error",
                    "message": "Incomplete 3D Secure data from gateway.",
                    "raw_response": raw_res,
                }

            return {"status": "3ds", "three_d_secure_data": tds_data, "raw_response": raw_res}

        elif status_code == const.STATUS_DUPLICATE:
            prev_status = result.get_previous_status_code()
            if prev_status == const.STATUS_SUCCESS:
                self.sudo().write({"provider_reference": result.get_cross_reference()})
                self._set_done()
                return {
                    "status": "done",
                    "cross_reference": result.get_cross_reference(),
                    "raw_response": raw_res,
                }
            else:
                error_msg = result.get_error_message() or result.get_message() or "Duplicate transaction"
                self._set_error(_(error_msg))
                return {"status": "error", "message": error_msg, "raw_response": raw_res}

        else:
            error_msg = result.get_error_message() or result.get_message() or "Payment failed"
            self._set_error(_(error_msg))
            return {"status": "error", "message": error_msg, "raw_response": raw_res}

    def _payvector_handle_3ds_fingerprint(self, method_data):
        self = self.with_context(payment_safe_write=True)
        self.ensure_one()
        provider = self.provider_id
        cross_reference = self.payvector_3ds_cross_reference

        if not cross_reference:
            return {"status": "error", "message": "Missing 3DS cross reference."}

        entry_points = provider._payvector_get_entry_points()

        tdse = ThreeDSecureEnvironment()
        tdse.merchant_authentication.merchant_id = provider.payvector_merchant_id
        tdse.merchant_authentication.password = provider.payvector_password
        tdse.request_gateway_entry_points = entry_points
        tdse.three_d_secure_environment_data.cross_reference = cross_reference
        tdse.three_d_secure_environment_data.method_data = method_data

        processed = tdse.process_transaction()

        if not processed:
            _logger.error(
                "PayVector ThreeDSecureEnvironment failed for %s: %s",
                self.reference,
                tdse.last_exception,
            )
            return {
                "status": "error",
                "message": "Could not communicate with payment gateway for 3DS fingerprint.",
            }

        result = tdse.three_d_secure_environment_result
        output = tdse.transaction_output_data

        if result and result.status_code == const.STATUS_3DS_REQUIRED:
            if output and output.three_d_secure_output_data:
                tds_out = output.three_d_secure_output_data
                if tds_out.creq:
                    new_cross_ref = output.cross_reference or cross_reference
                    self.sudo().write({
                        "payvector_3ds_cross_reference": new_cross_ref,
                    })
                    return {
                        "status": "challenge",
                        "acs_url": tds_out.acs_url,
                        "creq": tds_out.creq,
                        "cross_reference": new_cross_ref,
                    }

        if result and result.status_code == const.STATUS_SUCCESS:
            cross_ref = output.cross_reference if output else cross_reference
            self.sudo().write({"provider_reference": cross_ref})
            raw_card_type = None
            if output:
                if hasattr(output, "card_type_data") and output.card_type_data:
                    raw_card_type = getattr(output.card_type_data, "card_type", None) or getattr(output.card_type_data, "card_class", None)
                if not raw_card_type:
                    raw_card_type = getattr(output, "card_type", None)
            card_type = self._payvector_format_card_type(raw_card_type or self.payvector_temp_card_type)
            last_four = (getattr(output, "card_number_last_four", None) if output else None) or self.payvector_temp_last_four
            if last_four and not is_dummy_or_zero_last4(last_four):
                self._payvector_save_customer_card(
                    cross_ref,
                    last_four=last_four,
                    card_type=card_type,
                    expiry_month=self.payvector_temp_expiry_month,
                    expiry_year=self.payvector_temp_expiry_year,
                )
            self._set_done()
            return {"status": "done"}

        error_msg = result.message if result else "3D Secure verification failed"
        self._set_error(_(error_msg))
        return {"status": "error", "message": error_msg}

    def _payvector_handle_3ds_authentication(self, pa_res=None, cres=None):
        self = self.with_context(payment_safe_write=True)
        self.ensure_one()
        provider = self.provider_id
        cross_reference = self.payvector_3ds_cross_reference

        if not cross_reference:
            return {"status": "error", "message": "Missing 3DS cross reference."}

        entry_points = provider._payvector_get_entry_points()

        tx = ThreeDSecureAuthentication()
        tx.merchant_authentication.merchant_id = provider.payvector_merchant_id
        tx.merchant_authentication.password = provider.payvector_password
        tx.request_gateway_entry_points = entry_points
        tx.three_d_secure_input_data.cross_reference = cross_reference

        if cres:
            tx.three_d_secure_input_data.cres = cres
        elif pa_res:
            tx.three_d_secure_input_data.pa_res = pa_res

        processed = tx.process_transaction()

        if not processed:
            _logger.error(
                "PayVector ThreeDSecureAuthentication failed for %s: %s",
                self.reference,
                tx.last_exception,
            )
            self._set_error(
                _("Could not communicate with payment gateway for 3D Secure verification.")
            )
            return {
                "status": "error",
                "message": "Could not communicate with payment gateway.",
            }

        result = tx.three_d_secure_authentication_result
        output = tx.transaction_output_data

        if result and result.status_code == const.STATUS_SUCCESS:
            cross_ref = output.cross_reference if output else cross_reference
            self.sudo().write({"provider_reference": cross_ref})
            raw_card_type = None
            if output:
                if hasattr(output, "card_type_data") and output.card_type_data:
                    raw_card_type = getattr(output.card_type_data, "card_type", None) or getattr(output.card_type_data, "card_class", None)
                if not raw_card_type:
                    raw_card_type = getattr(output, "card_type", None)
            card_type = self._payvector_format_card_type(raw_card_type or self.payvector_temp_card_type)
            last_four = (getattr(output, "card_number_last_four", None) if output else None) or self.payvector_temp_last_four
            if last_four and not is_dummy_or_zero_last4(last_four):
                self._payvector_save_customer_card(
                    cross_ref,
                    last_four=last_four,
                    card_type=card_type,
                    expiry_month=self.payvector_temp_expiry_month,
                    expiry_year=self.payvector_temp_expiry_year,
                )
            self._set_done()
            return {"status": "done"}
        else:
            error_msg = (
                result.message if result else "3D Secure authentication failed"
            )
            self._set_error(_(error_msg))
            return {"status": "error", "message": error_msg}

    @api.model
    def _extract_reference(self, provider_code, payment_data):
        if provider_code != "payvector":
            return super()._extract_reference(provider_code, payment_data)
        data_ci = {str(k).lower(): v for k, v in payment_data.items()} if isinstance(payment_data, dict) else {}
        return (
            payment_data.get("OrderID")
            or data_ci.get("orderid")
            or data_ci.get("order_id")
            or data_ci.get("tx_reference")
            or payment_data.get("order_id")
            or payment_data.get("orderid")
            or payment_data.get("tx_reference")
        )

    @api.model
    def _get_tx_from_notification_data(self, provider_code, notification_data):
        if provider_code != "payvector":
            return super()._get_tx_from_notification_data(provider_code, notification_data)

        data_ci = {str(k).lower(): v for k, v in notification_data.items()} if isinstance(notification_data, dict) else {}

        reference = (
            notification_data.get("OrderID")
            or data_ci.get("orderid")
            or data_ci.get("order_id")
            or data_ci.get("tx_reference")
            or notification_data.get("order_id")
            or notification_data.get("orderid")
            or notification_data.get("tx_reference")
        )
        cross_ref = (
            notification_data.get("CrossReference")
            or data_ci.get("crossreference")
            or data_ci.get("cross_reference")
        )
        if not reference and cross_ref:
            tx = self.search(
                [("provider_reference", "=", cross_ref), ("provider_code", "=", "payvector")],
                limit=1,
            )
            if tx:
                return tx
            provider = self.env["payment.provider"].sudo().search([("code", "=", "payvector")], limit=1)
            if provider:
                gw_data = fetch_gateway_transaction_result(
                    provider.payvector_merchant_id,
                    provider.payvector_password,
                    cross_ref,
                )
                if gw_data and gw_data.get("OrderID"):
                    reference = gw_data.get("OrderID")
                    for k, v in gw_data.items():
                        if k not in notification_data:
                            notification_data[k] = v

        if reference:
            tx = self.search(
                [("reference", "=", reference), ("provider_code", "=", "payvector")],
                limit=1,
            )
            if tx:
                return tx

            if "-" in reference or "_" in reference:
                clean_ref = reference.split("-")[0] if "-" in reference else reference.split("_")[0]
                tx = self.search(
                    [("reference", "=", clean_ref), ("provider_code", "=", "payvector")],
                    limit=1,
                )
                if tx:
                    return tx

            tx = self.search(
                [
                    ("provider_code", "=", "payvector"),
                    "|",
                    ("reference", "=like", f"{reference}-%"),
                    ("reference", "=like", f"{reference}_%"),
                ],
                order="id desc",
                limit=1,
            )
            if tx:
                return tx

            tx = self.search(
                [
                    ("provider_code", "=", "payvector"),
                    ("reference", "ilike", reference),
                ],
                order="id desc",
                limit=1,
            )
            if tx:
                return tx

            try:
                clean_cand = reference.split("-")[0] if "-" in reference else (reference.split("_")[0] if "_" in reference else reference)
                tx = self.search(
                    [
                        ("provider_code", "=", "payvector"),
                        "|",
                        ("sale_order_ids.name", "=", reference),
                        ("sale_order_ids.name", "=", clean_cand),
                    ],
                    order="id desc",
                    limit=1,
                )
                if tx:
                    return tx
            except Exception:
                pass

        if request and hasattr(request, "session"):
            monitored_tx_id = request.session.get("__payment_monitored_tx_id__")
            if monitored_tx_id:
                tx = self.browse(monitored_tx_id).exists()
                if tx and tx.provider_code == "payvector":
                    return tx

        if cross_ref:
            tx = self.search(
                [
                    ("provider_code", "=", "payvector"),
                    "|",
                    ("provider_reference", "=", cross_ref),
                    ("payvector_3ds_cross_reference", "=", cross_ref),
                ],
                order="id desc",
                limit=1,
            )
            if tx:
                return tx

        return self

    @api.model
    def _search_by_reference(self, provider_code, payment_data):
        return self._get_tx_from_notification_data(provider_code, payment_data)

    def _extract_amount_data(self, payment_data, *args, **kwargs):
        if self.provider_code != "payvector":
            return super()._extract_amount_data(payment_data, *args, **kwargs)

        data = payment_data if isinstance(payment_data, dict) else (args[0] if args and isinstance(args[0], dict) else {})

        if "Amount" in data:
            try:
                minor_amount = float(data["Amount"])
                decimal_places = self.currency_id.decimal_places or 2
                amount = minor_amount / (10 ** decimal_places)
            except (ValueError, TypeError):
                amount = self.amount
        else:
            amount = self.amount

        return {
            "amount": amount,
            "currency_code": self.currency_id.name,
            "currency_id": self.currency_id.id,
        }

    def _process_notification_data(self, notification_data):
        if self.provider_code != "payvector":
            return super()._process_notification_data(notification_data)

        self = self.with_context(payment_safe_write=True)

        data_ci = {str(k).lower(): v for k, v in notification_data.items()} if isinstance(notification_data, dict) else {}

        def _get_val(*keys, default=None):
            for k in keys:
                if k in notification_data and notification_data[k] is not None and str(notification_data[k]).strip() != "":
                    return notification_data[k]
                kl = str(k).lower()
                if kl in data_ci and data_ci[kl] is not None and str(data_ci[kl]).strip() != "":
                    return data_ci[kl]
            return default

        if self.state in ["done", "cancel", "error"]:
            if self.state == "done" and self.partner_id and self.provider_reference:
                saved_card = self.env["payvector.saved.card"].sudo().search(
                    [("partner_id", "=", self.partner_id.id), ("cross_reference", "=", self.provider_reference)],
                    limit=1,
                )
                if not saved_card:
                    saved_card = self.env["payvector.saved.card"].sudo().search(
                        [("partner_id", "=", self.partner_id.id)],
                        limit=1,
                    )
                saved_last4 = (
                    _get_val("CardNumberLastFour", "CardLastFour", "CardLast4", "CardNumberLast4", "carno", "cardno", "LastFour", "Last4")
                    or (self.payvector_temp_last_four if not is_dummy_or_zero_last4(self.payvector_temp_last_four) else None)
                )
                if saved_last4 and not is_dummy_or_zero_last4(saved_last4):
                    digits_l4 = re.sub(r"\D", "", str(saved_last4))
                    if len(digits_l4) >= 4:
                        saved_last4 = digits_l4[-4:]

                    saved_card_type = (
                        _get_val("CardType", "cardtype", "CardClass")
                        or self.payvector_temp_card_type
                        or "Visa"
                    )
                    if not saved_card:
                        self._payvector_save_customer_card(
                            self.provider_reference,
                            last_four=saved_last4,
                            card_type=saved_card_type,
                            expiry_month=self.payvector_temp_expiry_month or "12",
                            expiry_year=self.payvector_temp_expiry_year or "35",
                        )
                    else:
                        saved_card.sudo().write({
                            "last_four": saved_last4,
                            "card_type": saved_card_type,
                            "cross_reference": self.provider_reference,
                        })
            return

        provider = self.provider_id
        psk = provider.payvector_pre_shared_key or ""
        hash_method = provider.payvector_hash_method or "SHA1"

        cross_ref_candidate = _get_val("CrossReference", "crossreference", "cross_reference")
        order_id_candidate = _get_val("OrderID", "orderid", "order_id") or self.reference
        digest = _get_val("HashDigest", "hashdigest", "hash_digest")

        if cross_ref_candidate and not self.provider_reference:
            self.sudo().write({"provider_reference": cross_ref_candidate})

        base_oids = list(filter(None, [
            order_id_candidate,
            self.reference,
            str(order_id_candidate).split("-")[0] if order_id_candidate and "-" in str(order_id_candidate) else None,
            str(order_id_candidate).split("_")[0] if order_id_candidate and "_" in str(order_id_candidate) else None,
            str(self.reference).split("-")[0] if self.reference and "-" in str(self.reference) else None,
            str(self.reference).split("_")[0] if self.reference and "_" in str(self.reference) else None,
        ]))
        if hasattr(self, "sale_order_ids") and self.sale_order_ids:
            for so in self.sale_order_ids:
                if so.name:
                    base_oids.append(so.name)
                    if "-" in so.name:
                        base_oids.append(so.name.split("-")[0])

        candidate_oids = []
        for o in base_oids:
            o_clean = str(o).strip()
            if o_clean and o_clean not in candidate_oids:
                candidate_oids.append(o_clean)
                if "-" not in o_clean and "_" not in o_clean:
                    for i in range(1, 10):
                        suffixed = f"{o_clean}-{i}"
                        if suffixed not in candidate_oids:
                            candidate_oids.append(suffixed)

        has_post_fields = _get_val("StatusCode") is not None and _get_val("Amount") is not None

        gw_data = {}
        if cross_ref_candidate:
            card_last4_val = _get_val("CardNumberLastFour", "CardLastFour", "CardLast4", "cardnumberlastfour")
            if not has_post_fields or is_dummy_or_zero_last4(card_last4_val):
                gw_data = fetch_gateway_transaction_result(
                    provider.payvector_merchant_id,
                    provider.payvector_password,
                    cross_ref_candidate,
                )
                if gw_data:
                    for k, v in gw_data.items():
                        is_card_key = str(k).lower() in ("cardnumberlastfour", "cardlastfour", "cardlast4", "lastfour", "last4")
                        if (
                            k not in notification_data
                            or notification_data[k] in (None, "")
                            or (is_card_key and is_dummy_or_zero_last4(notification_data[k]) and not is_dummy_or_zero_last4(v))
                        ):
                            notification_data[k] = v
                    data_ci = {str(k).lower(): v for k, v in notification_data.items()}
                    has_post_fields = _get_val("StatusCode") is not None and _get_val("Amount") is not None

        valid = False
        tr = None
        err_msg = ""

        post_vars = dict(notification_data)
        wire_keys = [
            'StatusCode', 'Message', 'PreviousStatusCode', 'PreviousMessage',
            'CrossReference', 'Amount', 'CurrencyCode', 'OrderID', 'TransactionType',
            'TransactionDateTime', 'OrderDescription', 'CustomerName', 'Address1',
            'Address2', 'Address3', 'Address4', 'City', 'State', 'PostCode',
            'CountryCode', 'CardType', 'CardClass', 'CardIssuer', 'CardNumberFirstSix',
            'CardNumberLastFour', 'CardExpiryDate', 'HashDigest',
            'AddressNumericCheckResult', 'PostCodeCheckResult', 'CV2CheckResult',
            'ThreeDSecureAuthenticationCheckResult', 'FraudProtectionCheckResult',
            'EmailAddress', 'PhoneNumber'
        ]
        for wk in wire_keys:
            if wk not in post_vars:
                val = data_ci.get(wk.lower())
                if val is not None:
                    post_vars[wk] = val

        get_hash_valid = False
        if digest and cross_ref_candidate:
            for oid in candidate_oids:
                for m in [hash_method, "SHA1", "MD5", "HMACSHA1", "HMACMD5"]:
                    str_to_hash = PaymentFormHelper.generate_string_to_hash3(
                        provider.payvector_merchant_id,
                        provider.payvector_password,
                        cross_ref_candidate,
                        oid,
                        psk,
                        m,
                    )
                    calc = PaymentFormHelper.calculate_hash_digest(str_to_hash, psk, m)
                    if calc and digest.upper() == calc.upper():
                        get_hash_valid = True
                        break
                if get_hash_valid:
                    break

        if get_hash_valid:
            valid = True

        if has_post_fields and not valid:
            ok, tr_parsed, dummy_hash, dummy_msg = PaymentFormHelper.get_transaction_result_from_post_variables(post_vars)
            if ok and tr_parsed:
                tr = tr_parsed
                candidate_digests = list(filter(None, [digest, post_vars.get("HashDigest"), gw_data.get("HashDigest") if gw_data else None]))
                for cd in candidate_digests:
                    for oid in candidate_oids:
                        tr.order_id = oid
                        for m in [hash_method, "SHA1", "MD5", "HMACSHA1", "HMACMD5"]:
                            for inc_empty in (True, False):
                                post_str = PaymentFormHelper.generate_string_to_hash2(
                                    provider.payvector_merchant_id,
                                    provider.payvector_password,
                                    tr,
                                    psk,
                                    m,
                                    include_empty_checks=inc_empty,
                                )
                                post_calc = PaymentFormHelper.calculate_hash_digest(post_str, psk, m)
                                if cd and post_calc and cd.upper() == post_calc.upper():
                                    valid = True
                                    break
                            if valid:
                                break
                        if valid:
                            break
                    if valid:
                        break

        if not valid and cross_ref_candidate:
            if not gw_data:
                gw_data = fetch_gateway_transaction_result(
                    provider.payvector_merchant_id,
                    provider.payvector_password,
                    cross_ref_candidate,
                )
                if gw_data:
                    for k, v in gw_data.items():
                        is_card_key = str(k).lower() in ("cardnumberlastfour", "cardlastfour", "cardlast4", "lastfour", "last4")
                        if (
                            k not in notification_data
                            or notification_data[k] in (None, "")
                            or (is_card_key and is_dummy_or_zero_last4(notification_data[k]) and not is_dummy_or_zero_last4(v))
                        ):
                            notification_data[k] = v
            if gw_data and "StatusCode" in gw_data:
                valid = True
                ok, tr_gw, dummy_hash, dummy_msg = PaymentFormHelper.get_transaction_result_from_post_variables(gw_data)
                if ok and tr_gw:
                    tr = tr_gw
            elif notification_data.get("StatusCode") is not None:
                pull_ok, pull_tr, pull_msg = PaymentFormHelper.get_transaction_result_from_payment_form_handler(
                    const.HOSTED_FORM_RESULT_HANDLER_URL,
                    provider.payvector_merchant_id,
                    provider.payvector_password,
                    cross_ref_candidate,
                )
                if pull_ok and pull_tr:
                    valid = True
                    tr = pull_tr
                    has_post_fields = True
                else:
                    err_msg = pull_msg or "Hash digests don't match - possible variable tampering"

        if not valid:
            self._set_error(_("Payment verification failed: %s.") % (err_msg or "Hash digests don't match - possible variable tampering"))
            return

        if not tr and has_post_fields:
            ok, tr_parsed, dummy_hash, dummy_msg = PaymentFormHelper.get_transaction_result_from_post_variables(post_vars)
            if ok:
                tr = tr_parsed

        def _non_dummy(val):
            if not val or is_dummy_or_zero_last4(val):
                return None
            return str(val).strip()

        last4_cand = (
            _non_dummy(_get_val("CardNumberLastFour", "CardLastFour", "CardLast4", "LastFour", "Last4", "carno", "cardno"))
            or _non_dummy(gw_data.get("CardNumberLastFour") if gw_data else None)
            or _non_dummy(getattr(tr, "card_number_last_four", None))
            or _non_dummy(self.payvector_temp_last_four)
            or False
        )
        if last4_cand:
            digits_l4 = re.sub(r"\D", "", str(last4_cand))
            if len(digits_l4) >= 4:
                last4_cand = digits_l4[-4:]
            elif is_dummy_or_zero_last4(digits_l4):
                last4_cand = False

        if not tr:
            card_type_cand = (
                _get_val("CardType", "cardtype", "CardClass")
                or (gw_data.get("CardType") if gw_data else None)
                or self.payvector_temp_card_type
                or "Visa"
            )
            minor_amt = provider._payvector_amount_to_minor(self.amount, self.currency_id) if hasattr(self, 'amount') else None
            curr_code = provider._payvector_get_iso_currency_code(self.currency_id) if hasattr(self, 'currency_id') else None
            tr = TransactionResult(
                status_code=0,
                message="Transaction verified via gateway hash digest",
                cross_reference=cross_ref_candidate or self.provider_reference,
                order_id=order_id_candidate or self.reference,
                amount=minor_amt,
                currency_code=curr_code,
                transaction_type="SALE",
                card_type=card_type_cand,
                card_number_last_four=last4_cand or None,
                card_expiry_date=(str(self.payvector_temp_expiry_month or "12") + str(self.payvector_temp_expiry_year or "35")[-2:]),
            )
        elif tr and last4_cand and not is_dummy_or_zero_last4(last4_cand):
            tr.card_number_last_four = last4_cand

        try:
            status_code = int(tr.status_code)
        except (ValueError, TypeError):
            try:
                status_code = int(_get_val("StatusCode", "statuscode"))
            except (ValueError, TypeError):
                status_code = -1

        try:
            prev_status_code = int(tr.previous_status_code)
        except (ValueError, TypeError):
            prev_status_code = None

        from thepaymentgateway.transaction_processor import HostedPaymentFormFinalTransactionResult
        final_result = HostedPaymentFormFinalTransactionResult(tr)

        raw_last_four = (
            _non_dummy(_get_val(
                "CardNumberLastFour",
                "CardLastFour",
                "CardLast4",
                "CardNumberLast4",
                "cardnumberlastfour",
                "cardlastfour",
                "cardlast4",
                "carno",
                "cardno",
                "CardNumber",
                "cardnumber",
                "LastFour",
                "Last4",
                "lastfour",
                "last4",
            ))
            or _non_dummy(gw_data.get("CardNumberLastFour") if gw_data else None)
            or _non_dummy(getattr(tr, "card_number_last_four", None))
            or _non_dummy(final_result.get_card_last_four() if hasattr(final_result, "get_card_last_four") else None)
            or _non_dummy(self.payvector_temp_last_four)
        )
        if raw_last_four and not is_dummy_or_zero_last4(raw_last_four):
            digits_last4 = re.sub(r"\D", "", str(raw_last_four))
            clean_last_four = digits_last4[-4:] if len(digits_last4) >= 4 else str(raw_last_four).strip()[-4:]
        else:
            clean_last_four = False

        raw_expiry = (
            (final_result.get_card_expiry_date() if hasattr(final_result, "get_card_expiry_date") else None)
            or getattr(tr, "get_card_expiry_date", lambda: None)()
            or getattr(tr, "card_expiry_date", None)
            or _get_val(
                "CardExpiryDate",
                "ExpiryDate",
                "CardExpiry",
                "cardexpirydate",
                "expirydate",
                "cardexpiry",
                "cardexp",
                "exp",
                "expiry",
            )
        )
        clean_exp_m = ""
        clean_exp_y = ""
        if raw_expiry:
            digits_exp = re.sub(r"\D", "", str(raw_expiry))
            if len(digits_exp) == 4:
                clean_exp_m = digits_exp[:2]
                clean_exp_y = digits_exp[2:]
            elif len(digits_exp) == 6:
                clean_exp_m = digits_exp[:2]
                clean_exp_y = digits_exp[4:]
        if not clean_exp_m and self.payvector_temp_expiry_month:
            clean_exp_m = self.payvector_temp_expiry_month
        if not clean_exp_y and self.payvector_temp_expiry_year:
            clean_exp_y = self.payvector_temp_expiry_year
        if not clean_exp_m:
            clean_exp_m = "12"
        if not clean_exp_y:
            clean_exp_y = "35"

        raw_card_type = (
            final_result.get_card_type()
            or getattr(tr, "get_card_type", lambda: None)()
            or getattr(tr, "card_type", None)
            or _get_val(
                "CardType",
                "CardClass",
                "CardIssuer",
                "cardtype",
                "cardclass",
                "cardissuer",
            )
            or self.payvector_temp_card_type
        )
        card_type = self._payvector_format_card_type(raw_card_type or "Visa")

        cross_reference = (
            getattr(tr, "cross_reference", None)
            or final_result.get_cross_reference()
            or _get_val("CrossReference", "crossreference", "cross_reference")
            or self.provider_reference
        )

        if status_code == const.STATUS_SUCCESS:
            vals_write = {
                "provider_reference": cross_reference,
                "payvector_temp_card_type": card_type,
                "payvector_temp_expiry_month": clean_exp_m,
                "payvector_temp_expiry_year": clean_exp_y,
            }
            if clean_last_four and not is_dummy_or_zero_last4(clean_last_four):
                vals_write["payvector_temp_last_four"] = clean_last_four
            self.sudo().write(vals_write)

            if clean_last_four and not is_dummy_or_zero_last4(clean_last_four):
                self._payvector_save_customer_card(
                    cross_reference,
                    last_four=clean_last_four,
                    card_type=card_type,
                    expiry_month=clean_exp_m,
                    expiry_year=clean_exp_y,
                )
            self._set_done()
        elif status_code == const.STATUS_DUPLICATE:
            if prev_status_code == const.STATUS_SUCCESS:
                vals_write = {
                    "provider_reference": cross_reference,
                    "payvector_temp_card_type": card_type,
                    "payvector_temp_expiry_month": clean_exp_m,
                    "payvector_temp_expiry_year": clean_exp_y,
                }
                if clean_last_four and not is_dummy_or_zero_last4(clean_last_four):
                    vals_write["payvector_temp_last_four"] = clean_last_four
                self.sudo().write(vals_write)

                if clean_last_four and not is_dummy_or_zero_last4(clean_last_four):
                    self._payvector_save_customer_card(
                        cross_reference,
                        last_four=clean_last_four,
                        card_type=card_type,
                        expiry_month=clean_exp_m,
                        expiry_year=clean_exp_y,
                    )
                self._set_done()
            else:
                self._set_error(
                    _(
                        "Duplicate transaction with previous status: %s"
                    ) % (tr.previous_message or tr.message or "Payment failed")
                )
        elif status_code == const.STATUS_DECLINED:
            self._set_error(
                _("Payment declined: %s") % (tr.message or "Card declined")
            )
        elif status_code == const.STATUS_REFERRED:
            self._set_error(
                _("Payment referred: %s") % (tr.message or "Card referred")
            )
        else:
            self._set_error(
                _(
                    "Payment failed (status %s): %s"
                ) % (status_code, tr.message or "Unknown error")
            )

    def _apply_updates(self, payment_data):
        return self._process_notification_data(payment_data)

    def _record(self, payment_data):
        return self._process_notification_data(payment_data)

    def _payvector_save_customer_card(
        self,
        cross_reference,
        last_four=None,
        card_type=None,
        expiry_month=None,
        expiry_year=None,
        **kwargs,
    ):
        self.ensure_one()
        provider = self.provider_id

        if not self.partner_id:
            return None
        if not cross_reference:
            return None

        if is_dummy_or_zero_last4(last_four):
            if self.payvector_temp_last_four and not is_dummy_or_zero_last4(self.payvector_temp_last_four):
                last_four = self.payvector_temp_last_four
        if is_dummy_or_zero_last4(last_four):
            return None

        if (not card_type or card_type == "Visa") and self.payvector_temp_card_type:
            card_type = self.payvector_temp_card_type
        if not expiry_month and self.payvector_temp_expiry_month:
            expiry_month = self.payvector_temp_expiry_month
        if not expiry_year and self.payvector_temp_expiry_year:
            expiry_year = self.payvector_temp_expiry_year

        saved_card = self.env["payvector.saved.card"].save_card_for_partner(
            partner_id=self.partner_id.id,
            cross_reference=cross_reference,
            last_four=last_four,
            card_type=card_type,
            expiry_month=expiry_month,
            expiry_year=expiry_year,
        )

        return saved_card
