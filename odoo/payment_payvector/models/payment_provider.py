# Part of Odoo. See LICENSE file for full copyright and licensing details.

import sys
import os

from odoo import api, fields, models

from odoo.addons.payment.logging import get_payment_logger
from odoo.addons.payment_payvector import const
from .payvector_saved_card import is_dummy_or_zero_last4


_lib_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), "lib")
if _lib_path not in sys.path:
    sys.path.insert(0, _lib_path)

from thepaymentgateway import (
    RequestGatewayEntryPointList,
    TransactionProcessor,
    PaymentFormHelper,
    HashMethod,
    ResultDeliveryMethod,
    TransactionType,
    ISOHelper,
)
from thepaymentgateway.transaction_processor import NativeSessionHandler

_logger = get_payment_logger(__name__)


class PaymentProvider(models.Model):
    _inherit = "payment.provider"

    code = fields.Selection(
        selection_add=[("payvector", "PayVector")],
        ondelete={"payvector": "set default"},
    )

    payvector_merchant_id = fields.Char(
        string="Merchant ID",
        help="Your PayVector Merchant ID.",
        required_if_provider="payvector",
        groups="base.group_system",
    )
    payvector_password = fields.Char(
        string="Merchant Password",
        help="Your PayVector Merchant Password.",
        required_if_provider="payvector",
        groups="base.group_system",
    )
    payvector_integration_mode = fields.Selection(
        string="Integration Mode",
        selection=[("direct", "Direct API"), ("hosted", "Hosted Payment Form")],
        default="hosted",
        required_if_provider="payvector",
    )
    payvector_pre_shared_key = fields.Char(
        string="Pre-Shared Key",
        help="Required for Hosted Payment Form hash verification.",
        groups="base.group_system",
    )
    payvector_hash_method = fields.Selection(
        string="Hash Method",
        selection=[
            ("SHA1", "SHA1"),
            ("MD5", "MD5"),
            ("HMACSHA1", "HMAC-SHA1"),
            ("HMACMD5", "HMAC-MD5"),
        ],
        default="SHA1",
    )
    payvector_result_delivery = fields.Selection(
        string="Result Delivery Method",
        selection=[("POST", "POST"), ("SERVER_PULL", "SERVER PULL")],
        default="POST",
    )
    payvector_show_saved_cards = fields.Boolean(
        string="Show Saved Cards",
        default=True,
        help="Allow customers to select and pay with saved cards.",
    )

    @api.model_create_multi
    def create(self, vals_list):
        providers = super().create(vals_list)
        for provider in providers:
            if provider.code == "payvector":
                if provider.payment_method_ids:
                    provider.payment_method_ids.filtered(
                        lambda m: m.code == "payvector"
                    ).write({"name": provider.name})
                provider._sync_payvector_form_views()
        return providers

    def write(self, vals):
        res = super().write(vals)
        for provider in self:
            if provider.code == "payvector":
                if "name" in vals and provider.payment_method_ids:
                    provider.payment_method_ids.filtered(
                        lambda m: m.code == "payvector"
                    ).write({"name": provider.name})
                if "payvector_integration_mode" in vals:
                    provider._sync_payvector_form_views()
        return res

    def _sync_payvector_form_views(self):
        for provider in self:
            if provider.code == "payvector":
                provider.inline_form_view_id = self.env.ref(
                    "payment_payvector.payvector_direct_form", raise_if_not_found=False
                )
                provider.redirect_form_view_id = self.env.ref(
                    "payment_payvector.redirect_form", raise_if_not_found=False
                )

    def _get_default_payment_method_codes(self):
        self.ensure_one()
        if self.code != "payvector":
            return super()._get_default_payment_method_codes()
        return const.DEFAULT_PAYMENT_METHOD_CODES


    def _payvector_get_entry_points(self):
        entry_points = RequestGatewayEntryPointList()
        for url, metric, retries in const.GATEWAY_ENTRY_POINTS:
            entry_points.add(url, metric, retries)
        return entry_points

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
            "DINERS CLUB": "Diners Club",
            "DINERS": "Diners Club",
            "JCB": "JCB",
            "MAESTRO": "Maestro",
            "UNIONPAY": "UnionPay",
        }
        return mapping.get(ct_upper, ct.capitalize())

    def _payvector_get_inline_form_values(self, partner_id=None):
        self.ensure_one()
        
        has_saved_card = False
        saved_card_last_four = ""
        saved_card_type = ""
        partner = None

        if partner_id:
            if isinstance(partner_id, int):
                partner = self.env["res.partner"].sudo().browse(partner_id)
            elif hasattr(partner_id, "id"):
                partner = partner_id.sudo()

        if not partner:
            try:
                from odoo.http import request
                if request:
                    if hasattr(request, "website") and request.website:
                        sale_order = request.website.sale_get_order()
                        if sale_order and sale_order.partner_id:
                            partner = sale_order.partner_id.sudo()
                    if not partner and hasattr(request, "env") and request.env.user:
                        if not request.env.user._is_public():
                            partner = request.env.user.partner_id.sudo()
            except Exception:
                pass
        if not partner:
            ctx_partner_id = self.env.context.get("partner_id")
            if ctx_partner_id:
                partner = self.env["res.partner"].sudo().browse(ctx_partner_id)
        if not partner:
            partner = self.env.user.partner_id.sudo() if self.env.user else None

        partner_ids = [partner.id] if partner else []
        if partner and hasattr(partner, "commercial_partner_id") and partner.commercial_partner_id:
            if partner.commercial_partner_id.id not in partner_ids:
                partner_ids.append(partner.commercial_partner_id.id)

        if self.payvector_show_saved_cards and partner_ids:
            saved_card = self.env["payvector.saved.card"].sudo().search(
                [("partner_id", "in", partner_ids), ("cross_reference", "!=", False)],
                order="id desc",
                limit=1,
            )
            if saved_card and saved_card.cross_reference:
                saved_card_last_four = str(saved_card.last_four or "").strip()
                if is_dummy_or_zero_last4(saved_card_last_four):
                    recent_txs = self.env["payment.transaction"].sudo().search(
                        [
                            ("partner_id", "in", partner_ids),
                            ("provider_id", "=", self.id),
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
            else:
                last_txs = self.env["payment.transaction"].sudo().search(
                    [
                        ("partner_id", "in", partner_ids),
                        ("provider_id", "=", self.id),
                        ("state", "=", "done"),
                        ("provider_reference", "!=", False),
                    ],
                    order="id desc",
                    limit=10,
                )
                found_tx = None
                for tx in last_txs:
                    if not is_dummy_or_zero_last4(tx.payvector_temp_last_four):
                        found_tx = tx
                        break
                if found_tx and found_tx.provider_reference:
                    has_saved_card = True
                    saved_card_last_four = str(found_tx.payvector_temp_last_four).strip()
                    saved_card_type = self._payvector_format_card_type(found_tx.payvector_temp_card_type) or "Card"
                else:
                    has_saved_card = False
                    saved_card_last_four = ""

        if not saved_card_last_four or is_dummy_or_zero_last4(saved_card_last_four):
            has_saved_card = False
            saved_card_last_four = ""

        current_year = fields.Date.today().year
        years = list(range(current_year, current_year + 15))

        return {
            "provider_id": self.id,
            "integration_mode": self.payvector_integration_mode,
            "payvector_mode": self.payvector_integration_mode,
            "has_saved_card": has_saved_card,
            "saved_card_last_four": saved_card_last_four,
            "saved_card_type": saved_card_type,
            "years": years,
            "partner_name": partner.name if partner else "",
        }

    def _payvector_amount_to_minor(self, amount, currency):
        iso_currencies = ISOHelper.get_iso_currency_list()
        exponent = 2  # Default
        for iso_cur in iso_currencies:
            if iso_cur.short_code == currency.name:
                exponent = iso_cur.priority  # priority field holds the exponent
                break
        return int(round(amount * (10 ** exponent)))

    def _payvector_get_iso_currency_code(self, currency):
        iso_currencies = ISOHelper.get_iso_currency_list()
        for iso_cur in iso_currencies:
            if iso_cur.short_code == currency.name:
                return iso_cur.iso_code
        return None

    def _payvector_get_iso_country_code(self, country_code_alpha2):
        if not country_code_alpha2:
            return None
        iso_countries = ISOHelper.get_iso_country_list()
        for iso_c in iso_countries:
            if iso_c.short_code == country_code_alpha2:
                return iso_c.iso_code
        return None
