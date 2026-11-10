# Part of Odoo. See LICENSE file for full copyright and licensing details.

import base64
import html
import json
import pprint
import re

from odoo import _, http
from odoo.exceptions import ValidationError
from odoo.http import request

from odoo.addons.payment.logging import get_payment_logger
from ..models.payvector_saved_card import is_dummy_or_zero_last4

_logger = get_payment_logger(__name__)

def fetch_gateway_transaction_result(merchant_id, password, cross_reference):
    if not merchant_id or not password or not cross_reference:
        return {}
    try:
        from urllib.parse import unquote, urlencode
        from urllib.request import Request, urlopen
        from .. import const
        from thepaymentgateway.payment_form_helper import PaymentFormHelper

        data = urlencode({
            "MerchantID": merchant_id,
            "Password": password,
            "CrossReference": str(cross_reference),
        }).encode("utf-8")
        req = Request(const.HOSTED_FORM_RESULT_HANDLER_URL, data=data, method="POST")
        with urlopen(req, timeout=30) as resp:
            body = resp.read().decode("utf-8")
        if not body:
            return {}
        parsed = PaymentFormHelper.parse_name_value_string_into_array(body, True)
        if int(parsed.get("StatusCode", "-1")) != 0:
            return {}
        if "TransactionResult" not in parsed:
            return {}
        raw_tr = parsed["TransactionResult"]
        result_vars = PaymentFormHelper.parse_name_value_string_into_array(unquote(raw_tr), True)
        return result_vars or {}
    except Exception:
        return {}


class PayVectorController(http.Controller):
    _return_url = "/payment/payvector/return"
    _server_result_url = "/payment/payvector/server_result"
    _direct_payment_url = "/payment/payvector/direct_payment"
    _3ds_callback_url = "/payment/payvector/3ds_callback"
    _3ds_return_url = "/payment/payvector/3ds_return"
    _3ds_data_url = "/payment/payvector/3ds_data"
    _3ds_url = "/payment/payvector/3ds"

    @http.route(
        _return_url,
        type="http",
        auth="public",
        methods=["GET", "POST"],
        csrf=False,
        save_session=False,
    )
    def payvector_return(self, **data):
        notification_data = {}
        if request and hasattr(request, "httprequest"):
            if hasattr(request.httprequest, "args"):
                notification_data.update(request.httprequest.args.to_dict())
            if hasattr(request.httprequest, "form"):
                notification_data.update(request.httprequest.form.to_dict())
            if not notification_data:
                try:
                    from urllib.parse import parse_qs
                    raw_body = request.httprequest.get_data(as_text=True)
                    if raw_body:
                        parsed = parse_qs(raw_body, keep_blank_values=True)
                        notification_data.update({k: v[0] if len(v) == 1 else v for k, v in parsed.items()})
                except Exception:
                    pass
        notification_data.update(data)

        cross_ref_candidate = (
            notification_data.get("CrossReference")
            or notification_data.get("crossreference")
            or notification_data.get("cross_reference")
        )
        has_post_fields = notification_data.get("StatusCode") is not None and notification_data.get("Amount") is not None
        card_last4_in_notif = (
            notification_data.get("CardNumberLastFour")
            or notification_data.get("CardLastFour")
            or notification_data.get("CardLast4")
            or notification_data.get("cardnumberlastfour")
        )
        if cross_ref_candidate and (not has_post_fields or is_dummy_or_zero_last4(card_last4_in_notif)):
            provider = request.env["payment.provider"].sudo().search([("code", "=", "payvector")], limit=1)
            if provider:
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
        try:
            tx_sudo = (
                request.env["payment.transaction"]
                .sudo()
                .with_context(payment_safe_write=True)
                ._get_tx_from_notification_data("payvector", notification_data)
            )
            if tx_sudo:
                tx_sudo = tx_sudo.with_context(payment_safe_write=True)
                tx_sudo._process_notification_data(notification_data)
                request.session["__payment_monitored_tx_id__"] = tx_sudo.id
                if tx_sudo.partner_id and tx_sudo.provider_reference and tx_sudo.state == "done":
                    raw_card_last4 = (
                        notification_data.get("CardNumberLastFour")
                        or notification_data.get("CardLastFour")
                        or notification_data.get("CardLast4")
                        or notification_data.get("LastFour")
                        or notification_data.get("cardnumberlastfour")
                        or tx_sudo.payvector_temp_last_four
                        or ""
                    )
                    card_last4 = ""
                    if raw_card_last4 and not is_dummy_or_zero_last4(raw_card_last4):
                        digits_last4 = re.sub(r"\D", "", str(raw_card_last4))
                        if len(digits_last4) >= 4:
                            card_last4 = digits_last4[-4:]
                        elif not is_dummy_or_zero_last4(digits_last4):
                            card_last4 = digits_last4

                    card_type = (
                        notification_data.get("CardType")
                        or notification_data.get("CardClass")
                        or notification_data.get("cardtype")
                        or tx_sudo.payvector_temp_card_type
                        or "Visa"
                    )
                    expiry_val = (
                        notification_data.get("CardExpiryDate")
                        or notification_data.get("ExpiryDate")
                        or notification_data.get("cardexpirydate")
                    )
                    expiry_month = tx_sudo.payvector_temp_expiry_month or "12"
                    expiry_year = tx_sudo.payvector_temp_expiry_year or "35"
                    if expiry_val:
                        digits_exp = re.sub(r"\D", "", str(expiry_val))
                        if len(digits_exp) == 4:
                            expiry_month, expiry_year = digits_exp[:2], digits_exp[2:]
                        elif len(digits_exp) == 6:
                            expiry_month, expiry_year = digits_exp[:2], digits_exp[4:]

                    if card_last4 and not is_dummy_or_zero_last4(card_last4):
                        saved_card = request.env["payvector.saved.card"].sudo().search(
                            [("partner_id", "=", tx_sudo.partner_id.id), ("cross_reference", "=", tx_sudo.provider_reference)],
                            limit=1,
                        )
                        if not saved_card:
                            saved_card = request.env["payvector.saved.card"].sudo().search(
                                [("partner_id", "=", tx_sudo.partner_id.id)],
                                limit=1,
                            )
                        if not saved_card:
                            tx_sudo._payvector_save_customer_card(
                                tx_sudo.provider_reference,
                                last_four=card_last4,
                                card_type=card_type,
                                expiry_month=expiry_month,
                                expiry_year=expiry_year,
                            )
                        else:
                            saved_card.write({
                                "last_four": card_last4,
                                "card_type": card_type,
                                "expiry_month": expiry_month,
                                "expiry_year": expiry_year,
                                "cross_reference": tx_sudo.provider_reference,
                            })
        except Exception as e:
            _logger.exception("PayVector callback error: %s", e)

        return request.redirect("/payment/status")

    @http.route(
        _server_result_url,
        type="http",
        auth="public",
        methods=["POST"],
        csrf=False,
        save_session=False,
    )
    def payvector_server_result(self, **data):
        notification_data = {}
        if request and hasattr(request, "httprequest"):
            if hasattr(request.httprequest, "args"):
                notification_data.update(request.httprequest.args.to_dict())
            if hasattr(request.httprequest, "form"):
                notification_data.update(request.httprequest.form.to_dict())
            if not notification_data:
                try:
                    from urllib.parse import parse_qs
                    raw_body = request.httprequest.get_data(as_text=True)
                    if raw_body:
                        parsed = parse_qs(raw_body, keep_blank_values=True)
                        notification_data.update({k: v[0] if len(v) == 1 else v for k, v in parsed.items()})
                except Exception:
                    pass
        notification_data.update(data)
        try:
            tx_sudo = (
                request.env["payment.transaction"]
                .sudo()
                .with_context(payment_safe_write=True)
                ._get_tx_from_notification_data("payvector", notification_data)
            )
            if tx_sudo:
                tx_sudo = tx_sudo.with_context(payment_safe_write=True)
                tx_sudo._process_notification_data(notification_data)
        except ValidationError:
            return "StatusCode=30&Message=Validation Failed"
        except Exception:
            return "StatusCode=30&Message=Server Error"

        return "StatusCode=0&Message=RECEIVED"

    @http.route(
        _direct_payment_url,
        type="http",
        auth="public",
        methods=["GET", "POST"],
        csrf=False,
    )
    def payvector_direct_payment(self, **data):
        reference = data.get("reference")
        provider_id = int(data.get("provider_id", 0))

        if not reference or not provider_id:
            return request.redirect("/payment/status")

        tx_sudo = (
            request.env["payment.transaction"]
            .sudo()
            .search(
                [
                    ("reference", "=", reference),
                    ("provider_id", "=", provider_id),
                    ("provider_code", "=", "payvector"),
                ],
                limit=1,
            )
        )

        if not tx_sudo and reference:
            tx_sudo = (
                request.env["payment.transaction"]
                .sudo()
                .search(
                    [
                        ("provider_id", "=", provider_id),
                        ("provider_code", "=", "payvector"),
                        "|",
                        ("reference", "=like", f"{reference}-%"),
                        ("reference", "=like", f"{reference}_%"),
                    ],
                    order="id desc",
                    limit=1,
                )
            )

        if not tx_sudo and reference and ("-" in reference or "_" in reference):
            clean_ref = reference.split("-")[0] if "-" in reference else reference.split("_")[0]
            tx_sudo = (
                request.env["payment.transaction"]
                .sudo()
                .search(
                    [
                        ("reference", "=", clean_ref),
                        ("provider_id", "=", provider_id),
                        ("provider_code", "=", "payvector"),
                    ],
                    order="id desc",
                    limit=1,
                )
            )

        if not tx_sudo:
            return request.redirect("/payment/status")

        payvector_saved_card = data.get("payvector_saved_card", "new")
        saved_cc_cvc = (
            data.get("saved_cc_cvc", "")
            or data.get("payvector_saved_cc_cvv", "")
            or data.get("cc_cvc", "")
        )

        cross_reference = None
        use_saved_card = False

        if payvector_saved_card == "saved" and tx_sudo.partner_id:
            partner_ids = [tx_sudo.partner_id.id]
            if tx_sudo.partner_id.commercial_partner_id and tx_sudo.partner_id.commercial_partner_id.id not in partner_ids:
                partner_ids.append(tx_sudo.partner_id.commercial_partner_id.id)

            saved_card = (
                request.env["payvector.saved.card"]
                .sudo()
                .search(
                    [("partner_id", "in", partner_ids), ("cross_reference", "!=", False)],
                    order="id desc",
                    limit=1,
                )
            )
            if saved_card and saved_card.cross_reference:
                cross_reference = saved_card.cross_reference
                use_saved_card = True
            else:
                last_tx = request.env["payment.transaction"].sudo().search(
                    [
                        ("provider_id", "=", tx_sudo.provider_id.id),
                        ("partner_id", "in", partner_ids),
                        ("state", "=", "done"),
                        ("provider_reference", "!=", False),
                    ],
                    order="id desc",
                    limit=1,
                )
                if last_tx and last_tx.provider_reference:
                    cross_reference = last_tx.provider_reference
                    use_saved_card = True

        card_number = (
            data.get("cc_number")
            or data.get("ccnumber")
            or data.get("cardnum")
            or ""
        )
        expiry_month = (
            data.get("cc_expiry_month")
            or data.get("ccexpirymonth")
            or data.get("expiry_month")
            or data.get("exp_month")
            or ""
        )
        expiry_year = (
            data.get("cc_expiry_year")
            or data.get("ccexpiryyear")
            or data.get("expiry_year")
            or data.get("exp_year")
            or ""
        )
        cardexp = data.get("cardexp") or data.get("CardExpiryDate") or data.get("expiry")
        if cardexp and (not expiry_month or not expiry_year):
            clean_exp = re.sub(r"\D", "", str(cardexp))
            if len(clean_exp) == 4:
                if not expiry_month:
                    expiry_month = clean_exp[:2]
                if not expiry_year:
                    expiry_year = clean_exp[2:]
            elif len(clean_exp) == 6:
                if not expiry_month:
                    expiry_month = clean_exp[:2]
                if not expiry_year:
                    expiry_year = clean_exp[4:]

        if expiry_month:
            clean_m = re.sub(r"\D", "", str(expiry_month))
            expiry_month = f"0{clean_m}" if len(clean_m) == 1 else clean_m[:2]
        if expiry_year:
            clean_y = re.sub(r"\D", "", str(expiry_year))
            expiry_year = clean_y[-2:] if len(clean_y) >= 2 else clean_y

        cv2 = saved_cc_cvc or data.get("cc_cvc", "") or data.get("cccvv", "")
        cardholder_name = (
            data.get("cc_holder_name")
            or data.get("payvector_cc_owner")
            or data.get("ccholdername")
            or ""
        )

        if tx_sudo.provider_id.payvector_integration_mode == "hosted":
            if not use_saved_card or not cross_reference:
                tx_sudo._set_error(_("Hosted mode does not support direct capture without a saved card."))
                return request.redirect("/payment/status")

        clean_card_digits = re.sub(r"\D", "", str(card_number or ""))
        if not use_saved_card and not clean_card_digits:
            tx_sudo._set_error(_("Credit card number is required."))
            return request.redirect("/payment/status")

        browser_data = {
            "java_enabled": data.get("browser_java_enabled", "false").lower() == "true",
            "javascript_enabled": True,
            "screen_width": int(data.get("browser_screen_width", 1920)),
            "screen_height": int(data.get("browser_screen_height", 1080)),
            "color_depth": int(data.get("browser_color_depth", 24)),
            "timezone": int(data.get("browser_tz", 0)),
            "language": data.get("browser_language", "en-US"),
            "ip_address": request.httprequest.remote_addr or "127.0.0.1",
        }

        base_url = tx_sudo.provider_id.get_base_url()

        result = tx_sudo._payvector_process_direct_payment(
            card_number=card_number,
            expiry_month=expiry_month,
            expiry_year=expiry_year,
            cv2=cv2,
            cardholder_name=cardholder_name,
            browser_data=browser_data,
            base_url=base_url,
            use_saved_card=use_saved_card,
            cross_reference=cross_reference,
        )



        if result["status"] == "done":
            return request.redirect("/payment/status")

        elif result["status"] == "3ds":
            tds = result["three_d_secure_data"]
            target = tds.get("target", "_self")

            if target in ("_self", "_parent", ""):
                return self._render_3ds_direct_post(
                    tds.get("acs_url", ""),
                    tds.get("params", {}),
                )

            return self._render_3ds_challenge_page(
                tds.get("acs_url", ""),
                tds.get("params", {}),
                target=target,
            )

        else:
            return request.redirect("/payment/status")

    def _render_3ds_direct_post(self, acs_url, form_params):
        hidden_inputs = "".join(
            f'<input type="hidden" name="{html.escape(str(k), quote=True)}" value="{html.escape(str(v), quote=True)}" />\n'
            for k, v in (form_params or {}).items()
        )
        html_content = f"""<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>3D Secure Verification</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; text-align: center; padding-top: 80px; background-color: #f8f9fa;">
    <div style="max-width: 500px; margin: 0 auto; padding: 40px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 12px; color: #333;">3D Secure Verification</h3>
        <p style="color: #666; font-size: 14px; margin-bottom: 24px;">
            Connecting to your card issuer for verification, please wait...
        </p>
        <form id="threeds_direct_form" action="{html.escape(acs_url, quote=True)}" method="POST" target="_top">
            {hidden_inputs}
        </form>
    </div>
    <script type="text/javascript">
        (function() {{
            var form = document.getElementById('threeds_direct_form');
            if (form) {{
                form.target = "_top";
                form.submit();
            }}
        }})();
    </script>
</body>
</html>"""
        return request.make_response(html_content, headers={"Content-Type": "text/html; charset=utf-8"})

    def _render_3ds_challenge_page(self, acs_url, form_params, target="threeDSecureFrame"):
        hidden_inputs = "".join(
            f'<input type="hidden" name="{html.escape(str(k), quote=True)}" value="{html.escape(str(v), quote=True)}" />\n'
            for k, v in (form_params or {}).items()
            if k != "target"
        )
        html_content = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>3D Secure Verification</title>
    <style>
        * {{
            box-sizing: border-box;
        }}
        body {{
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333333;
        }}
        .pv-container {{
            max-width: 900px;
            margin: 20px auto;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            overflow: hidden;
        }}
        .pv-header {{
            padding: 24px 30px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }}
        .pv-header h3 {{
            margin: 0 0 10px 0;
            font-size: 22px;
            font-weight: 600;
            color: #212529;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }}
        .pv-header p {{
            margin: 0;
            font-size: 14px;
            color: #6c757d;
            line-height: 1.5;
        }}
        .pv-frame-wrap {{
            width: 100%;
            min-height: 750px;
            background: #ffffff;
        }}
        iframe {{
            width: 100%;
            min-width: 100%;
            height: 750px;
            min-height: 750px;
            border: 0;
            display: block;
        }}
    </style>
</head>
<body>
    <div class="pv-container">
        <div class="pv-header">
            <h3>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0d6efd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                3D Secure Verification
            </h3>
            <p>
                You are enrolled for 3D Secure Verification. Your card will not be charged until you verify the transaction. For your security, please complete the verification below. Do not close or refresh this page.
            </p>
        </div>
        <div class="pv-frame-wrap">
            <iframe name="threeDSecureFrame" id="threeDSecureFrame" title="3D Secure Authentication Frame"></iframe>
        </div>
        <form id="threeds_form" action="{html.escape(acs_url, quote=True)}" method="POST" target="{html.escape(target, quote=True)}">
            {hidden_inputs}
        </form>
    </div>
    <script type="text/javascript">
        (function() {{
            function submit3DS() {{
                var form = document.getElementById('threeds_form');
                if (form) {{
                    form.submit();
                }}
            }}
            if (document.readyState === 'loading') {{
                document.addEventListener('DOMContentLoaded', submit3DS);
            }} else {{
                submit3DS();
            }}
        }})();
    </script>
</body>
</html>"""
        return request.make_response(html_content, headers={"Content-Type": "text/html; charset=utf-8"})

    @http.route(
        [_3ds_data_url, _3ds_callback_url],
        type="http",
        auth="public",
        methods=["GET", "POST"],
        csrf=False,
        save_session=False,
    )
    def payvector_3ds_data(self, **data):
        params = {}
        if request and hasattr(request, "httprequest"):
            if hasattr(request.httprequest, "form"):
                params.update(request.httprequest.form.to_dict())
            if hasattr(request.httprequest, "args"):
                params.update(request.httprequest.args.to_dict())
        params.update(data)

        tx_reference = params.get("tx_reference")
        form_url = self._3ds_return_url
        if tx_reference:
            form_url = f"{form_url}?tx_reference={tx_reference}"

        hidden_inputs = "".join(
            f'<input type="hidden" name="{html.escape(str(k), quote=True)}" value="{html.escape(str(v), quote=True)}" />\n'
            for k, v in params.items()
        )

        html_content = f"""<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>3D Secure</title>
</head>
<body>
    <form id="three_ds_data_form" action="{html.escape(form_url, quote=True)}" method="POST" target="_top">
        {hidden_inputs}
    </form>
    <script type="text/javascript">
        (function() {{
            var form = document.getElementById('three_ds_data_form');
            if (form) {{
                if (typeof HTMLFormElement !== 'undefined' && HTMLFormElement.prototype.submit) {{
                    HTMLFormElement.prototype.submit.call(form);
                }} else {{
                    form.submit();
                }}
            }}
        }})();
    </script>
</body>
</html>"""
        return request.make_response(html_content, headers={"Content-Type": "text/html; charset=utf-8"})

    @http.route(
        _3ds_return_url,
        type="http",
        auth="public",
        methods=["GET", "POST"],
        csrf=False,
    )
    def payvector_3ds_return(self, **data):
        params = {}
        if request and hasattr(request, "httprequest"):
            if hasattr(request.httprequest, "args"):
                params.update(request.httprequest.args.to_dict())
            if hasattr(request.httprequest, "form"):
                params.update(request.httprequest.form.to_dict())
        params.update(data)

        cres = params.get("cres", "")
        pa_res = params.get("PaRes", "") or params.get("pares", "")
        md = params.get("MD", "") or params.get("md", "")
        three_ds_session_data = params.get("threeDSSessionData", "")
        tx_reference = params.get("tx_reference", "")

        tx_sudo = None
        if tx_reference:
            tx_sudo = (
                request.env["payment.transaction"]
                .sudo()
                .search(
                    [
                        ("reference", "=", tx_reference),
                        ("provider_code", "=", "payvector"),
                    ],
                    limit=1,
                )
            )

        cross_ref = params.get("cross_reference", "") or md
        if not cross_ref and three_ds_session_data:
            try:
                padding = len(three_ds_session_data) % 4
                padded = three_ds_session_data + ("=" * (4 - padding) if padding else "")
                cross_ref = base64.urlsafe_b64decode(padded).decode("utf-8")
            except Exception:
                cross_ref = three_ds_session_data

        if not tx_sudo and cross_ref:
            tx_sudo = (
                request.env["payment.transaction"]
                .sudo()
                .search(
                    [
                        ("payvector_3ds_cross_reference", "=", cross_ref),
                        ("provider_code", "=", "payvector"),
                    ],
                    limit=1,
                )
            )

        if tx_sudo:
            request.session["__payment_monitored_tx_id__"] = tx_sudo.id

        if not tx_sudo:
            return request.redirect("/payment/status")

        if cres:
            if cross_ref and not tx_sudo.payvector_3ds_cross_reference:
                tx_sudo.sudo().write({"payvector_3ds_cross_reference": cross_ref})
            tx_sudo._payvector_handle_3ds_authentication(cres=cres)
            return request.redirect("/payment/status")

        if pa_res:
            if cross_ref and not tx_sudo.payvector_3ds_cross_reference:
                tx_sudo.sudo().write({"payvector_3ds_cross_reference": cross_ref})
            tx_sudo._payvector_handle_3ds_authentication(pa_res=pa_res)
            return request.redirect("/payment/status")

        return request.redirect("/payment/status")

    @http.route(
        _3ds_url,
        type="http",
        auth="public",
        methods=["GET", "POST"],
        csrf=False,
    )
    def payvector_3ds(self, **data):
        params = {}
        if request and hasattr(request, "httprequest"):
            if hasattr(request.httprequest, "args"):
                params.update(request.httprequest.args.to_dict())
            if hasattr(request.httprequest, "form"):
                params.update(request.httprequest.form.to_dict())
        params.update(data)

        cres = params.get("cres", "")
        pa_res = params.get("PaRes", "") or params.get("pares", "")
        md = params.get("MD", "") or params.get("md", "")
        three_ds_method_data = (
            params.get("threeDSMethodData", "")
            or params.get("ThreeDSMethodData", "")
        )
        three_ds_session_data = params.get("threeDSSessionData", "")
        tx_reference = params.get("tx_reference", "")

        tx_sudo = None
        if tx_reference:
            tx_sudo = (
                request.env["payment.transaction"]
                .sudo()
                .search(
                    [
                        ("reference", "=", tx_reference),
                        ("provider_code", "=", "payvector"),
                    ],
                    limit=1,
                )
            )

        cross_ref = params.get("cross_reference", "") or md
        if not cross_ref and three_ds_session_data:
            try:
                padding = len(three_ds_session_data) % 4
                padded = three_ds_session_data + ("=" * (4 - padding) if padding else "")
                cross_ref = base64.urlsafe_b64decode(padded).decode("utf-8")
            except Exception:
                cross_ref = three_ds_session_data

        if not tx_sudo and cross_ref:
            tx_sudo = (
                request.env["payment.transaction"]
                .sudo()
                .search(
                    [
                        ("payvector_3ds_cross_reference", "=", cross_ref),
                        ("provider_code", "=", "payvector"),
                    ],
                    limit=1,
                )
            )

        if tx_sudo:
            request.session["__payment_monitored_tx_id__"] = tx_sudo.id

        if not tx_sudo:
            return request.redirect("/payment/status")

        if cres:
            if cross_ref and not tx_sudo.payvector_3ds_cross_reference:
                tx_sudo.sudo().write({"payvector_3ds_cross_reference": cross_ref})
            tx_sudo._payvector_handle_3ds_authentication(cres=cres)
            return request.redirect("/payment/status")

        if three_ds_method_data:
            result = tx_sudo._payvector_handle_3ds_fingerprint(three_ds_method_data)
            if result.get("status") == "challenge":
                session_data = base64.urlsafe_b64encode(
                    (result.get("cross_reference") or "").encode("utf-8")
                ).decode("utf-8").rstrip("=")
                return self._render_3ds_challenge_page(
                    result.get("acs_url", ""),
                    {
                        "creq": result.get("creq", ""),
                        "threeDSSessionData": session_data,
                    },
                    target="threeDSecureFrame",
                )
            else:
                return request.redirect("/payment/status")

        if pa_res:
            if cross_ref and not tx_sudo.payvector_3ds_cross_reference:
                tx_sudo.sudo().write({"payvector_3ds_cross_reference": cross_ref})
            tx_sudo._payvector_handle_3ds_authentication(pa_res=pa_res)
            return request.redirect("/payment/status")

        return request.redirect("/payment/status")
