from __future__ import annotations
import base64, hashlib, hmac, html
from dataclasses import dataclass
from datetime import datetime
from urllib.parse import unquote, urlencode
from urllib.request import Request, urlopen
_RESULT_WIRE_NAMES = {
    'status_code': 'StatusCode',
    'message': 'Message',
    'previous_status_code': 'PreviousStatusCode',
    'previous_message': 'PreviousMessage',
    'cross_reference': 'CrossReference',
    'address_numeric_check_result': 'AddressNumericCheckResult',
    'post_code_check_result': 'PostCodeCheckResult',
    'cv2_check_result': 'CV2CheckResult',
    'three_d_secure_authentication_check_result': 'ThreeDSecureAuthenticationCheckResult',
    'fraud_protection_check_result': 'FraudProtectionCheckResult',
    'card_type': 'CardType',
    'card_class': 'CardClass',
    'card_issuer': 'CardIssuer',
    'card_issuer_country_code': 'CardIssuerCountryCode',
    'card_number_first_six': 'CardNumberFirstSix',
    'card_number_last_four': 'CardNumberLastFour',
    'card_expiry_date': 'CardExpiryDate',
    'amount': 'Amount',
    'currency_code': 'CurrencyCode',
    'order_id': 'OrderID',
    'transaction_type': 'TransactionType',
    'transaction_date_time': 'TransactionDateTime',
    'order_description': 'OrderDescription',
    'customer_name': 'CustomerName',
    'address1': 'Address1',
    'address2': 'Address2',
    'address3': 'Address3',
    'address4': 'Address4',
    'city': 'City',
    'state': 'State',
    'post_code': 'PostCode',
    'country_code': 'CountryCode',
    'email_address': 'EmailAddress',
    'phone_number': 'PhoneNumber',
    'merchant_id': 'MerchantID',
    'acs_url': 'ACSURL',
    'pa_req': 'PaREQ',
    'callback_url': 'CallbackUrl',
    'pa_res': 'PaRES',
}
_RESULT_FIELDS = tuple(_RESULT_WIRE_NAMES)

class TransactionResult:
    def __init__(self, **kwargs):
        unknown = set(kwargs) - set(_RESULT_FIELDS)
        if unknown:
            raise TypeError(f"Unexpected field(s): {', '.join(sorted(unknown))}")
        for field in _RESULT_FIELDS:
            setattr(self, field, kwargs.get(field))

    def __getattr__(self, name):
        if name.startswith('get_'):
            field = name[4:]
            if field in _RESULT_FIELDS:
                return lambda field=field: getattr(self, field)
        if name.startswith('set_'):
            field = name[4:]
            if field in _RESULT_FIELDS:
                return lambda value, field=field: setattr(self, field, value)
        raise AttributeError(name)

@dataclass
class ListItem:
    name: str
    value: str
    is_selected: bool = False

    def get_name(self):
        return self.name

    def get_value(self):
        return self.value

    def get_is_selected(self):
        return self.is_selected

class ListItemList:

    def __init__(self):
        self._items = []

    def get_count(self):
        return len(self._items)

    def get_at(self, index):
        if index < 0 or index >= len(self._items):
            raise IndexError('Array index out of bounds')
        return self._items[index]

    def add(self, name, value, is_selected=False):
        self._items.append(ListItem(str(name), str(value), bool(is_selected)))

    def to_string(self):
        parts = []
        for i in self._items:
            val = f' value="{html.escape(i.value, quote=True)}"' if i.value not in (None, '') else ''
            sel = ' selected="selected"' if i.is_selected else ''
            parts.append(f'<option{val}{sel}>{html.escape(i.name)}</option>\n')
        return ''.join(parts)

    def __iter__(self):
        return iter(self._items)

class PaymentFormHelper:

    @staticmethod
    def bool_to_string(value):
        return 'true' if value else 'false'

    @staticmethod
    def string_to_bool(value):
        return str(value).upper() == 'TRUE'

    @staticmethod
    def base64_url_encode(value):
        b = value.encode() if isinstance(value, str) else bytes(value)
        return base64.urlsafe_b64encode(b).decode().rstrip('=')

    @staticmethod
    def base64_url_decode(value):
        s = str(value)
        s += '=' * ((4 - len(s) % 4) % 4)
        return base64.urlsafe_b64decode(s)

    @staticmethod
    def add_string_to_string_list(existing, to_add):
        return existing if not to_add else (existing + ', ' if existing else '') + to_add

    @staticmethod
    def parse_name_value_string_into_array(value, url_decode_values):
        out = {}
        if not value:
            return out
        for pair in value.split('&'):
            name, sep, val = pair.partition('=')
            out[name] = unquote(val) if url_decode_values else val
        return out

    @staticmethod
    def create_expiry_date_month_list_item_list(month):
        out = ListItemList()
        for n in range(1, 13):
            out.add(f'{n:02d}', f'{n:02d}', str(month) in (str(n), f'{n:02d}'))
        return out

    @staticmethod
    def create_expiry_date_year_list_item_list(year):
        out = ListItemList()
        now = datetime.now().year
        for y in range(now, now + 11):
            out.add(str(y), str(y)[-2:], str(year) == str(y)[-2:])
        return out

    @staticmethod
    def create_iso_country_list_item_list(country_short, countries):
        out = ListItemList()
        items = sorted(list(countries), key=lambda x: (-x.priority, x.name))
        for x in items:
            out.add(x.name, x.short_code, x.short_code == country_short)
        return out

    @staticmethod
    def calculate_hash_digest(input_string, pre_shared_key, hash_method):
        method = str(hash_method).upper().replace('-', '').replace('_', '')
        data = str(input_string).encode()
        key = str(pre_shared_key or '').encode()
        if method == 'MD5':
            return hashlib.md5(data).hexdigest()
        if method == 'SHA1':
            return hashlib.sha1(data).hexdigest()
        if method == 'HMACMD5':
            return hmac.new(key, data, hashlib.md5).hexdigest()
        if method == 'HMACSHA1':
            return hmac.new(key, data, hashlib.sha1).hexdigest()
        raise ValueError(f'Unsupported hash method: {hash_method}')

    @staticmethod
    def _prefix(psk, method):
        m = str(method).upper().replace('-', '').replace('_', '')
        return f'PreSharedKey={psk}&' if m in ('MD5', 'SHA1') else ''

    @staticmethod
    def generate_string_to_hash(merchant_id, password, amount, currency_code, order_id, transaction_type, transaction_datetime, callback_url, order_description, customer_name, address1, address2, address3, address4, city, state, post_code, country_code, cv2_mandatory, address1_mandatory, city_mandatory, post_code_mandatory, state_mandatory, country_mandatory, result_delivery_method, server_result_url, payment_form_displays_result, pre_shared_key, hash_method, email_address, phone_number):
        p = PaymentFormHelper._prefix(pre_shared_key, hash_method)
        vals = [('MerchantID', merchant_id), ('Password', password), ('Amount', amount), ('CurrencyCode', currency_code), ('EchoAVSCheckResult', 'true'), ('EchoCV2CheckResult', 'true'), ('EchoThreeDSecureAuthenticationCheckResult', 'true'), ('EchoCardType', 'true'), ('EchoCardNumberFirstSix', 'true'), ('EchoCardNumberLastFour', 'true'), ('OrderID', order_id), ('TransactionType', transaction_type), ('TransactionDateTime', transaction_datetime), ('CallbackURL', callback_url), ('OrderDescription', order_description), ('CustomerName', customer_name), ('Address1', address1), ('Address2', address2), ('Address3', address3), ('Address4', address4), ('City', city), ('State', state), ('PostCode', post_code), ('CountryCode', country_code), ('EmailAddress', email_address), ('PhoneNumber', phone_number), ('CV2Mandatory', cv2_mandatory), ('Address1Mandatory', address1_mandatory), ('CityMandatory', city_mandatory), ('PostCodeMandatory', post_code_mandatory), ('StateMandatory', state_mandatory), ('CountryMandatory', country_mandatory), ('ResultDeliveryMethod', result_delivery_method), ('ServerResultURL', server_result_url), ('PaymentFormDisplaysResult', payment_form_displays_result), ('ServerResultURLCookieVariables', ''), ('ServerResultURLFormVariables', ''), ('ServerResultURLQueryStringVariables', '')]
        return p + '&'.join((f"{k}={('' if v is None else v)}" for k, v in vals))

    @staticmethod
    def generate_string_to_hash3(merchant_id, password, cross_reference, order_id, pre_shared_key, hash_method):
        m_id = str(merchant_id or '').strip()
        pwd = str(password or '').strip()
        c_ref = str(cross_reference or '').strip()
        o_id = str(order_id or '').strip()
        psk = str(pre_shared_key or '').strip()
        return PaymentFormHelper._prefix(psk, hash_method) + f'MerchantID={m_id}&Password={pwd}&CrossReference={c_ref}&OrderID={o_id}'

    @staticmethod
    def generate_string_to_hash_transparent(merchant_id, password, amount, currency_code, order_id, transaction_type, transaction_datetime, callback_url, order_description, customer_name, address1, address2, address3, address4, city, state, post_code, country_code, secret_key):
        vals = [('MerchantID', merchant_id), ('Password', password), ('Amount', amount), ('CurrencyCode', currency_code), ('OrderID', order_id), ('TransactionType', transaction_type), ('TransactionDateTime', transaction_datetime), ('CallbackURL', callback_url), ('OrderDescription', order_description), ('CustomerName', customer_name), ('Address1', address1), ('Address2', address2), ('Address3', address3), ('Address4', address4), ('City', city), ('State', state), ('PostCode', post_code), ('CountryCode', country_code), ('SecretKey', secret_key)]
        return '&'.join((f"{k}={('' if v is None else v)}" for k, v in vals))

    @staticmethod
    def generate_string_to_hash_initial(hash_method, pre_shared_key, merchant_id, password, amount, currency_code, order_id, transaction_type, transaction_datetime, callback_url, order_description):
        return PaymentFormHelper._prefix(pre_shared_key, hash_method) + f"MerchantID={merchant_id}&Password={password}&Amount={amount}&CurrencyCode={currency_code}&EchoAVSCheckResult=true&EchoCV2CheckResult=true&EchoThreeDSecureAuthenticationCheckResult=true&EchoCardType=true&EchoCardNumberFirstSix=true&EchoCardNumberLastFour=true&OrderID={order_id}&TransactionType={transaction_type}&TransactionDateTime={transaction_datetime}&CallbackURL={callback_url}&OrderDescription={order_description or ''}"

    @staticmethod
    def generate_string_to_hash2(merchant_id, password, tr, pre_shared_key, hash_method, include_empty_checks=True):
        def _v(val):
            return "" if val is None else str(val)

        psk = str(pre_shared_key or '').strip()
        prefix = PaymentFormHelper._prefix(psk, hash_method)
        m_id = _v(merchant_id).strip()
        pwd = _v(password).strip()

        s = (
            prefix
            + f"MerchantID={m_id}&Password={pwd}"
            + f"&StatusCode={_v(tr.status_code)}&Message={_v(tr.message)}"
            + f"&PreviousStatusCode={_v(tr.previous_status_code)}&PreviousMessage={_v(tr.previous_message)}"
            + f"&CrossReference={_v(tr.cross_reference)}"
        )
        has_an = (tr.address_numeric_check_result is not None) if include_empty_checks else (tr.address_numeric_check_result not in (None, ""))
        if has_an:
            s += f"&AddressNumericCheckResult={_v(tr.address_numeric_check_result)}&PostCodeCheckResult={_v(tr.post_code_check_result)}"
        has_cv2 = (tr.cv2_check_result is not None) if include_empty_checks else (tr.cv2_check_result not in (None, ""))
        if has_cv2:
            s += f"&CV2CheckResult={_v(tr.cv2_check_result)}"
        has_3ds = (tr.three_d_secure_authentication_check_result is not None) if include_empty_checks else (tr.three_d_secure_authentication_check_result not in (None, ""))
        if has_3ds:
            s += f"&ThreeDSecureAuthenticationCheckResult={_v(tr.three_d_secure_authentication_check_result)}"
        has_card = (tr.card_type is not None) if include_empty_checks else (tr.card_type not in (None, ""))
        if has_card:
            s += (
                f"&CardType={_v(tr.card_type)}&CardClass={_v(tr.card_class)}"
                f"&CardIssuer={_v(tr.card_issuer)}&CardIssuerCountryCode={_v(tr.card_issuer_country_code)}"
            )
        s += (
            f"&CardNumberFirstSix={_v(tr.card_number_first_six)}&CardNumberLastFour={_v(tr.card_number_last_four)}"
            f"&Amount={_v(tr.amount)}&CurrencyCode={_v(tr.currency_code)}"
            f"&OrderID={_v(tr.order_id)}&TransactionType={_v(tr.transaction_type)}"
            f"&TransactionDateTime={_v(tr.transaction_date_time)}&OrderDescription={_v(tr.order_description)}"
            f"&CustomerName={_v(tr.customer_name)}&Address1={_v(tr.address1)}"
            f"&Address2={_v(tr.address2)}&Address3={_v(tr.address3)}"
            f"&Address4={_v(tr.address4)}&City={_v(tr.city)}"
            f"&State={_v(tr.state)}&PostCode={_v(tr.post_code)}"
            f"&CountryCode={_v(tr.country_code)}"
        )
        has_email = (tr.email_address is not None) if include_empty_checks else (tr.email_address not in (None, ""))
        if has_email:
            s += f"&EmailAddress={_v(tr.email_address)}"
        has_phone = (tr.phone_number is not None) if include_empty_checks else (tr.phone_number not in (None, ""))
        if has_phone:
            s += f"&PhoneNumber={_v(tr.phone_number)}"
        return s

    @staticmethod
    def get_transaction_reference_from_query_string(v):
        v_ci = {str(k).lower(): val for k, val in v.items()} if isinstance(v, dict) else {}
        cross_ref = v.get('CrossReference') or v_ci.get('crossreference') or v_ci.get('cross_reference')
        order_id = v.get('OrderID') or v_ci.get('orderid') or v_ci.get('order_id')
        digest = v.get('HashDigest') or v_ci.get('hashdigest') or v_ci.get('hash_digest') or ''
        missing = []
        if not cross_ref:
            missing.append('CrossReference')
        if not order_id:
            missing.append('OrderID')
        return (not missing, cross_ref, order_id, digest, ', '.join(f'Expected variable [{k}] not received' for k in missing))

    @staticmethod
    def get_transaction_result_from_post_variables(v):
        if not isinstance(v, dict):
            return (False, None, '', 'Invalid post variables format')
        v_ci = {str(k).lower(): val for k, val in v.items()}
        required = [
            'StatusCode', 'Message', 'PreviousStatusCode', 'PreviousMessage',
            'CrossReference', 'Amount', 'CurrencyCode', 'OrderID',
            'TransactionType', 'TransactionDateTime', 'OrderDescription',
            'CustomerName', 'Address1', 'Address2', 'Address3', 'Address4',
            'City', 'State', 'PostCode', 'CountryCode'
        ]
        essential = ['StatusCode', 'Message', 'CrossReference', 'Amount', 'CurrencyCode', 'OrderID']
        missing_essential = [k for k in essential if k not in v and k.lower() not in v_ci]
        if missing_essential:
            return (False, None, v.get('HashDigest', '') or v_ci.get('hashdigest', ''), ', '.join(f'Expected variable [{k}] not received' for k in missing_essential))

        tr = TransactionResult()
        int_fields = {'status_code', 'previous_status_code', 'card_issuer_country_code', 'amount', 'currency_code', 'country_code'}
        for field, wire_name in _RESULT_WIRE_NAMES.items():
            key = 'CallbackURL' if wire_name == 'CallbackUrl' else wire_name
            val = v.get(key)
            if val is None:
                val = v_ci.get(key.lower())
            if val is None and wire_name in required:
                val = ''
            if val is not None:
                parsed_val = (
                    None if val in (None, '') and field in int_fields
                    else int(val) if field in int_fields and val not in (None, '') and str(val).strip().isdigit()
                    else val
                )
                setattr(tr, field, parsed_val)

        # Explicit check for card aliases
        if not getattr(tr, 'card_number_last_four', None):
            last4_alias = (
                v.get('CardNumberLastFour')
                or v_ci.get('cardnumberlastfour')
                or v.get('CardLastFour')
                or v_ci.get('cardlastfour')
                or v.get('CardLast4')
                or v_ci.get('cardlast4')
                or v.get('LastFour')
                or v_ci.get('lastfour')
                or v.get('Last4')
                or v_ci.get('last4')
            )
            if last4_alias:
                tr.card_number_last_four = str(last4_alias)

        digest = v.get('HashDigest') or v_ci.get('hashdigest') or ''
        return (True, tr, digest, '')

    @staticmethod
    def validate_transaction_result_post(merchant_id, password, pre_shared_key, hash_method, post_variables):
        ok, tr, digest, msg = PaymentFormHelper.get_transaction_result_from_post_variables(post_variables)
        if not ok:
            return (False, tr, msg)
        calc = PaymentFormHelper.calculate_hash_digest(PaymentFormHelper.generate_string_to_hash2(merchant_id, password, tr, pre_shared_key, hash_method), pre_shared_key, hash_method)
        return (digest.upper() == calc.upper(), tr, '' if digest.upper() == calc.upper() else "Hash digests don't match - possible variable tampering")

    @staticmethod
    def get_transaction_result_from_payment_form_handler(url, merchant_id, password, cross_reference, timeout=30):
        data = urlencode({'MerchantID': str(merchant_id).strip(), 'Password': str(password).strip(), 'CrossReference': str(cross_reference).strip()}).encode()
        with urlopen(Request(url, data=data, method='POST'), timeout=timeout) as r:
            response = r.read().decode()
        if not response:
            return (False, None, 'Received empty response from payment form handler')
        parsed = PaymentFormHelper.parse_name_value_string_into_array(response, True)
        if int(parsed.get('StatusCode', '-1')) != 0:
            return (False, None, parsed.get('Message', f'Received invalid response from payment form handler [{response}]'))
        if 'TransactionResult' not in parsed:
            return (False, None, f'No transaction result in response from payment form handler [{response}]')
        result_vars = PaymentFormHelper.parse_name_value_string_into_array(unquote(parsed['TransactionResult']), True)
        ok, tr, dummy_hash, msg = PaymentFormHelper.get_transaction_result_from_post_variables(result_vars)
        if not ok and 'StatusCode' in result_vars:
            try:
                sc = int(result_vars.get('StatusCode', '-1'))
            except (ValueError, TypeError):
                sc = -1
            if sc >= 0:
                prev_sc = None
                if 'PreviousStatusCode' in result_vars and str(result_vars['PreviousStatusCode']).isdigit():
                    prev_sc = int(result_vars['PreviousStatusCode'])
                tr = TransactionResult(
                    status_code=sc,
                    message=result_vars.get('Message', 'AUTHED' if sc == 0 else 'Payment Failed'),
                    previous_status_code=prev_sc,
                    previous_message=result_vars.get('PreviousMessage'),
                    cross_reference=result_vars.get('CrossReference', cross_reference),
                    order_id=result_vars.get('OrderID'),
                    amount=result_vars.get('Amount'),
                    currency_code=result_vars.get('CurrencyCode'),
                    card_number_last_four=result_vars.get('CardNumberLastFour') or result_vars.get('CardLastFour') or result_vars.get('CardLast4'),
                    card_type=result_vars.get('CardType', 'Visa'),
                )
                return (True, tr, '')
        return (ok, tr, msg)

def _generate_3ds_required(tr, hash_method, psk, merchant_id, password):
    p = PaymentFormHelper._prefix(psk, hash_method)
    return p + f'MerchantID={merchant_id}&Password={password}&StatusCode={tr.get_status_code()}&Message={tr.get_message()}&CrossReference={tr.get_cross_reference()}&OrderID={tr.get_order_id()}&TransactionDateTime={tr.get_transaction_date_time()}&ACSURL={tr.get_acs_url()}&PaREQ={tr.get_pa_req()}'

def _generate_3ds_post(hash_method, psk, merchant_id, password, cross_reference, transaction_datetime, callback_url, pares):
    p = PaymentFormHelper._prefix(psk, hash_method)
    return p + f'MerchantID={merchant_id}&Password={password}&CrossReference={cross_reference}&TransactionDateTime={transaction_datetime}&CallbackURL={callback_url}&PaRES={pares}'

def _generate_payment_complete(tr, hash_method, psk, merchant_id, password):
    p = PaymentFormHelper._prefix(psk, hash_method)
    s = p + f'MerchantID={merchant_id}&Password={password}&StatusCode={tr.get_status_code()}&Message={tr.get_message()}&PreviousStatusCode={tr.get_previous_status_code()}&PreviousMessage={tr.get_previous_message()}&CrossReference={tr.get_cross_reference()}'
    optional = [('AddressNumericCheckResult', tr.get_address_numeric_check_result()), ('CV2CheckResult', tr.get_cv2_check_result()), ('ThreeDSecureAuthenticationCheckResult', tr.get_three_d_secure_authentication_check_result()), ('FraudProtectionCheckResult', tr.get_fraud_protection_check_result())]
    for k, v in optional:
        if v is not None:
            s += f'&{k}={v}'
            if k == 'AddressNumericCheckResult':
                s += f'&PostCodeCheckResult={tr.get_post_code_check_result()}'
    if tr.get_card_type() is not None:
        s += f'&CardType={tr.get_card_type()}&CardClass={tr.get_card_class()}&CardIssuer={tr.get_card_issuer()}&CardIssuerCountryCode={tr.get_card_issuer_country_code()}'
    for k, getter in [('CardNumberFirstSix', tr.get_card_number_first_six), ('CardNumberLastFour', tr.get_card_number_last_four), ('CardExpiryDate', tr.get_card_expiry_date)]:
        v = getter()
        if v is not None:
            s += f'&{k}={v}'
    s += f'&Amount={tr.get_amount()}&CurrencyCode={tr.get_currency_code()}&OrderID={tr.get_order_id()}&TransactionType={tr.get_transaction_type()}&TransactionDateTime={tr.get_transaction_date_time()}&OrderDescription={tr.get_order_description()}&Address1={tr.get_address1()}&Address2={tr.get_address2()}&Address3={tr.get_address3()}&Address4={tr.get_address4()}&City={tr.get_city()}&State={tr.get_state()}&PostCode={tr.get_post_code()}&CountryCode={tr.get_country_code()}&EmailAddress={tr.get_email_address()}&PhoneNumber={tr.get_phone_number()}'
    return s

def _validate_server(merchant_id, password, psk, hash_method, query_variables, storage_getter=None):
    ok, cross_ref, order_id, digest, msg = PaymentFormHelper.get_transaction_reference_from_query_string(query_variables)
    if not ok:
        return (False, None, msg)
    calc = PaymentFormHelper.calculate_hash_digest(PaymentFormHelper.generate_string_to_hash3(merchant_id, password, cross_ref, order_id, psk, hash_method), psk, hash_method)
    if digest.upper() != calc.upper():
        return (False, None, "Hash digests don't match - possible variable tampering")
    if storage_getter is None:
        return (False, None, 'SERVER delivery requires a storage_getter(cross_reference, order_id) callback in Python')
    try:
        tr = storage_getter(cross_ref, order_id)
        return (tr is not None, tr, '' if tr is not None else 'Transaction result not found in storage')
    except Exception as exc:
        return (False, None, str(exc))

def _validate_server_pull(merchant_id, password, psk, hash_method, query_variables, result_handler_url, timeout=30):
    ok, cross_ref, order_id, digest, msg = PaymentFormHelper.get_transaction_reference_from_query_string(query_variables)
    if not ok:
        return (False, None, msg)
    calc = PaymentFormHelper.calculate_hash_digest(PaymentFormHelper.generate_string_to_hash3(merchant_id, password, cross_ref, order_id, psk, hash_method), psk, hash_method)
    if digest.upper() != calc.upper():
        return (False, None, "Hash digests don't match - possible variable tampering")
    ok, tr, msg = PaymentFormHelper.get_transaction_result_from_payment_form_handler(result_handler_url, merchant_id, password, cross_ref, timeout)
    return (ok, tr, msg)

def _get_site_secure_base_url(environ):
    https = str(environ.get('HTTPS', '')).lower() == 'on'
    scheme = 'https' if https else 'http'
    default = 443 if https else 80
    port = int(environ.get('SERVER_PORT', default))
    host = environ.get('SERVER_NAME', 'localhost')
    script = environ.get('SCRIPT_NAME', '/')
    prefix = f'{scheme}://{host}' + (f':{port}' if port != default else '')
    return prefix + script[:script.rfind('/') + 1]
PaymentFormHelper.generate_string_to_hash3_d_secure_authentication_required = staticmethod(_generate_3ds_required)
PaymentFormHelper.generate_string_to_hash3_d_secure_post_authentication = staticmethod(_generate_3ds_post)
PaymentFormHelper.generate_string_to_hash_payment_complete = staticmethod(_generate_payment_complete)
PaymentFormHelper.validate_transaction_result_server = staticmethod(_validate_server)
PaymentFormHelper.validate_transaction_result_server_pull = staticmethod(_validate_server_pull)
PaymentFormHelper.get_site_secure_base_url = staticmethod(_get_site_secure_base_url)
