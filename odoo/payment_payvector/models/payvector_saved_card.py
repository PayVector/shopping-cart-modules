import logging
import re

from odoo import api, fields, models, _

_logger = logging.getLogger(__name__)


def is_dummy_or_zero_last4(val):
    if not val:
        return True
    s = str(val).strip()
    digits = re.sub(r"\D", "", s)
    if not digits:
        return True
    if not digits.replace("0", ""):  
        return True
    return False


class PayVectorSavedCard(models.Model):
    _name = "payvector.saved.card"
    _description = "PayVector Customer Saved Card"
    _order = "id desc"

    partner_id = fields.Many2one(
        "res.partner",
        string="Customer",
        required=True,
        ondelete="cascade",
        index=True,
    )
    cross_reference = fields.Char(
        string="Cross Reference Token",
        required=True,
    )
    card_type = fields.Char(
        string="Card Type",
    )
    last_four = fields.Char(
        string="Last Four Digits",
    )
    expiry_month = fields.Char(
        string="Expiry Month",
    )
    expiry_year = fields.Char(
        string="Expiry Year",
    )
    date_added = fields.Datetime(
        string="Date Added",
        default=fields.Datetime.now,
    )

    _sql_constraints = [
        (
            "partner_unique",
            "unique(partner_id)",
            "Only one saved card per customer is allowed.",
        )
    ]

    def write(self, vals):
        if "last_four" in vals:
            if is_dummy_or_zero_last4(vals.get("last_four")):
                vals = dict(vals)
                del vals["last_four"]
                if not vals:
                    return True
        return super().write(vals)

    @api.model_create_multi
    def create(self, vals_list):
        valid_vals = []
        for v in vals_list:
            if is_dummy_or_zero_last4(v.get("last_four")):
                continue
            valid_vals.append(v)
        if not valid_vals:
            return self.browse()
        return super().create(valid_vals)

    @api.model
    def save_card_for_partner(
        self,
        partner_id,
        cross_reference,
        last_four=None,
        card_type=None,
        expiry_month=None,
        expiry_year=None,
        **kwargs,
    ):
        if not partner_id or not cross_reference:
            return False

        if is_dummy_or_zero_last4(last_four):
            return False

        clean_last4 = ""
        if last_four:
            digits = re.sub(r"\D", "", str(last_four))
            clean_last4 = digits[-4:] if len(digits) >= 4 else str(last_four).strip()[-4:]

        if is_dummy_or_zero_last4(clean_last4):
            return False
        old_tokens = self.env["payment.token"].sudo().search([
            ("provider_id.code", "=", "payvector"),
            ("partner_id", "=", partner_id),
        ])
        if old_tokens:
            old_tokens.unlink()

        saved_card = self.sudo().search([("partner_id", "=", partner_id)], limit=1)

        clean_card_type = str(card_type).strip() if card_type else "Visa"

        clean_exp_m = ""
        clean_exp_y = ""
        if expiry_month:
            m_digits = re.sub(r"\D", "", str(expiry_month))
            clean_exp_m = f"0{m_digits}" if len(m_digits) == 1 else m_digits[:2]
        if expiry_year:
            y_digits = re.sub(r"\D", "", str(expiry_year))
            clean_exp_y = y_digits[-2:] if len(y_digits) >= 2 else y_digits
        if not clean_exp_m:
            clean_exp_m = "12"
        if not clean_exp_y:
            clean_exp_y = "35"

        vals = {
            "cross_reference": str(cross_reference).strip(),
            "last_four": clean_last4,
            "card_type": clean_card_type,
            "expiry_month": clean_exp_m,
            "expiry_year": clean_exp_y,
            "date_added": fields.Datetime.now(),
        }

        if saved_card:
            if is_dummy_or_zero_last4(clean_last4) and saved_card.last_four and not is_dummy_or_zero_last4(saved_card.last_four):
                vals["last_four"] = saved_card.last_four
            saved_card.write(vals)
        else:
            vals["partner_id"] = partner_id
            saved_card = self.sudo().create([vals])

        return saved_card
