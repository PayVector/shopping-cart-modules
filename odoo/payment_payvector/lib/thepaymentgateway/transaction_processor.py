from __future__ import annotations
from datetime import datetime, timezone
from .client import CardDetailsTransaction, CrossReferenceTransaction, ThreeDSecureAuthentication
from .payment_form_helper import PaymentFormHelper, TransactionResult

class BasicEnum:
    NONE = None

    @classmethod
    def is_valid_name(cls, name, strict=False):
        keys = [k for k in cls.__dict__ if not k.startswith('_') and k not in {'is_valid_name', 'is_valid_value'}]
        return name in keys if strict else str(name).lower() in {k.lower() for k in keys}

    @classmethod
    def is_valid_value(cls, value):
        return value in [getattr(cls, k) for k in cls.__dict__ if not k.startswith('_')]

class IntegrationMethod(BasicEnum):
    DIRECT_API = 'Direct API'
    HOSTED_PAYMENT_FORM = 'Hosted Payment Form'
    TRANSPARENT_REDIRECT = 'Transparent Redirect'

class TransactionMethod(BasicEnum):
    CARD_DETAILS_TRANSACTION = 'CardDetailsTransaction'
    CROSS_REFERENCE_TRANSACTION = 'CrossReferenceTransaction'
    THREE_D_SECURE_TRANSACTION = 'ThreeDSecureTransaction'

class TransactionResultCode(BasicEnum):
    SUCCESSFUL = 0
    INCOMPLETE = 3
    REFERRED = 4
    DECLINED = 5
    DUPLICATE_TRANSACTION = 20
    FAILED = 30

class TransactionType(BasicEnum):
    SALE = 'SALE'
    PRE_AUTH = 'PREAUTH'
    REFUND = 'REFUND'
    COLLECTION = 'COLLECTION'
    VOID = 'VOID'
    RETRY = 'Retry'

class HashMethod(BasicEnum):
    SHA1 = 'SHA1'
    MD5 = 'MD5'
    HMAC_SHA1 = 'HMACSHA1'
    HMAC_MD5 = 'HMACMD5'

class ResultDeliveryMethod(BasicEnum):
    POST = 'POST'
    SERVER = 'SERVER'
    SERVER_PULL = 'SERVER_PULL'

class SaleType(BasicEnum):
    NEW_SALE = 'new_card'
    CROSS_REFERENCE_SALE = 'stored_card'

class PayVectorSessionHandler:

    def initialise_session(self):
        raise NotImplementedError

    def set_session_value(self, key, value):
        raise NotImplementedError

    def get_session_value(self, key):
        raise NotImplementedError

    def unset_session_value(self, key):
        raise NotImplementedError

class NativeSessionHandler(PayVectorSessionHandler):
    """Simple in-process session store for non-framework Python use.
    Web frameworks can provide their own PayVectorSessionHandler implementation.
    """

    def __init__(self, store=None):
        self.store = {} if store is None else store

    def initialise_session(self):
        return self

    def set_session_value(self, key, value):
        self.store[key] = value

    def get_session_value(self, key):
        return self.store.get(key)

    def unset_session_value(self, key):
        self.store.pop(key, None)

class BaseFinalTransactionResult:
    integration_method = IntegrationMethod.NONE
    transaction_processed_value = False

    def get_integration_method(self):
        return self.integration_method

    def get_transaction_method(self):
        return None

    def get_order_id(self, session_handler=None):
        return None

    def transaction_processed(self):
        return self.transaction_processed_value

    def transaction_successful(self):
        return self.get_status_code() == 0 or self.get_previous_status_code() == 0

    def get_gateway_entry_point_list(self):
        return None

    def authorisation_attempted(self):
        return None

    def get_status_code(self):
        return None

    def get_previous_status_code(self):
        return None

    def get_message(self):
        return None

    def duplicate_transaction(self):
        return None

    def get_cross_reference(self):
        return None

    def get_auth_code(self):
        return None

    def get_address_numeric_check_result(self):
        return None

    def get_post_code_check_result(self):
        return None

    def get_cv2_check_result(self):
        return None

    def get_error_message(self):
        return None

    def get_card_type(self):
        return None

    def get_card_issuer(self):
        return None

    def get_card_first_six(self, session_handler=None):
        return None

    def get_card_last_four(self, session_handler=None):
        return None

    def get_three_d_secure_output_data(self):
        return None

    def get_user_friendly_message(self):
        if self.get_status_code() == 0 or self.get_previous_status_code() == 0:
            return 'Transaction Successful. ' + str(self.get_message() or '')
        if self.get_status_code() == 4 or self.get_previous_status_code() == 4:
            return 'Card Referred'
        if self.get_status_code() == 5 or self.get_previous_status_code() == 5:
            return 'Card Declined'
        return 'Error'

class DirectFinalTransactionResult(BaseFinalTransactionResult):
    integration_method = IntegrationMethod.DIRECT_API
    transaction_method = None

    def __init__(self, processed, transaction, result, output, session_handler):
        self.transaction_processed_value = processed
        self.to_transaction_object = transaction
        self.tro_transaction_result_object = result
        self.tod_transaction_output_data = output
        if self.get_status_code() == 3 and session_handler:
            session_handler.set_session_value('payvector_transaction_order_id', self.get_order_id(session_handler))

    def get_transaction_method(self):
        return self.transaction_method

    def get_order_id(self, session_handler=None):
        return self.to_transaction_object.transaction_details.order_id if hasattr(self.to_transaction_object, 'transaction_details') else None

    def authorisation_attempted(self):
        return getattr(self.tro_transaction_result_object, 'authorisation_attempted', None) if self.tro_transaction_result_object else None

    def get_status_code(self):
        return getattr(self.tro_transaction_result_object, 'status_code', 30) if self.transaction_processed_value and self.tro_transaction_result_object else 30

    def get_previous_status_code(self):
        p = getattr(self.tro_transaction_result_object, 'previous_transaction_result', None) if self.tro_transaction_result_object else None
        return getattr(p, 'status_code', None) if p else None

    def get_message(self):
        if not self.transaction_processed_value:
            return 'Could not communicate with payment gateway'
        if self.get_status_code() == 20:
            p = getattr(self.tro_transaction_result_object, 'previous_transaction_result', None)
            return getattr(p, 'message', None)
        return getattr(self.tro_transaction_result_object, 'message', None)

    def duplicate_transaction(self):
        return self.get_status_code() == 20

    def _out(self, name):
        return getattr(self.tod_transaction_output_data, name, None) if self.tod_transaction_output_data else None

    def get_cross_reference(self):
        return self._out('cross_reference')

    def get_auth_code(self):
        return self._out('auth_code')

    def get_address_numeric_check_result(self):
        return self._out('address_numeric_check_result')

    def get_post_code_check_result(self):
        return self._out('post_code_check_result')

    def get_cv2_check_result(self):
        return self._out('cv2_check_result')

    def get_three_d_secure_output_data(self):
        return self._out('three_d_secure_output_data')

    def get_gateway_entry_point_list(self):
        return self._out('gateway_entry_points')

    def get_card_type(self):
        c = self._out('card_type_data')
        return getattr(c, 'card_type', None) if c else None

    def get_card_issuer(self):
        c = self._out('card_type_data')
        return getattr(c, 'issuer', None) if c else None

    def get_error_message(self):
        if not self.transaction_processed_value:
            return 'Could not communicate with the payment gateway.'
        parts = [getattr(self.tro_transaction_result_object, 'message', None)] + list(getattr(self.tro_transaction_result_object, 'error_messages', []) or [])
        return ', '.join((str(x) for x in parts if x))

class CardDetailsFinalTransactionResult(DirectFinalTransactionResult):
    transaction_method = TransactionMethod.CARD_DETAILS_TRANSACTION

    def __init__(self, *args):
        super().__init__(*args)
        session = args[-1]
        card = getattr(args[1].card_details, 'card_number', None) if args[1] else None
        if session and card:
            session.set_session_value('payvector_transaction_card_first_six', str(card)[:6])
            session.set_session_value('payvector_transaction_card_last_four', str(card)[-4:])

    def get_card_first_six(self, session_handler=None):
        c = getattr(self.to_transaction_object.card_details, 'card_number', None)
        return str(c)[:6] if c and len(str(c)) >= 6 else None

    def get_card_last_four(self, session_handler=None):
        c = getattr(self.to_transaction_object.card_details, 'card_number', None)
        return str(c)[-4:] if c and len(str(c)) >= 4 else None

class CrossReferenceFinalTransactionResult(DirectFinalTransactionResult):
    transaction_method = TransactionMethod.CROSS_REFERENCE_TRANSACTION

class ThreeDSecureFinalTransactionResult(DirectFinalTransactionResult):
    transaction_method = TransactionMethod.THREE_D_SECURE_TRANSACTION

    def get_three_d_secure_authentication_check_result(self):
        return self._out('three_d_secure_authentication_check_result')

    def _session_pop(self, session, key):
        if not session:
            return None
        v = session.get_session_value(key)
        if v not in (None, ''):
            session.unset_session_value(key)
        return v

    def get_card_first_six(self, session_handler=None):
        return self._session_pop(session_handler, 'payvector_transaction_card_first_six')

    def get_card_last_four(self, session_handler=None):
        return self._session_pop(session_handler, 'payvector_transaction_card_last_four')

    def get_order_id(self, session_handler=None):
        return self._session_pop(session_handler, 'payvector_transaction_order_id')

class HostedPaymentFormFinalTransactionResult(BaseFinalTransactionResult):
    integration_method = IntegrationMethod.HOSTED_PAYMENT_FORM

    def __init__(self, tr):
        self.transaction_processed_value = True
        self.tr_transaction_result = tr

    def get_order_id(self, session_handler=None):
        return self.tr_transaction_result.order_id

    def get_status_code(self):
        return self.tr_transaction_result.status_code

    def get_previous_status_code(self):
        return self.tr_transaction_result.previous_status_code

    def get_message(self):
        return self.tr_transaction_result.previous_message if self.get_status_code() == 20 else self.tr_transaction_result.message

    def duplicate_transaction(self):
        return self.get_status_code() == 20

    def get_cross_reference(self):
        return self.tr_transaction_result.cross_reference

    def get_error_message(self):
        return self.tr_transaction_result.message

    def get_card_type(self):
        return self.tr_transaction_result.card_type

    def get_card_first_six(self, session_handler=None):
        return self.tr_transaction_result.card_number_first_six

    def get_card_last_four(self, session_handler=None):
        return self.tr_transaction_result.card_number_last_four

    def get_address_numeric_check_result(self):
        return self.tr_transaction_result.address_numeric_check_result

    def get_post_code_check_result(self):
        return self.tr_transaction_result.post_code_check_result

    def get_cv2_check_result(self):
        return self.tr_transaction_result.cv2_check_result

    def get_card_issuer(self):
        return self.tr_transaction_result.card_issuer

class TransparentRedirectFinalTransactionResult(HostedPaymentFormFinalTransactionResult):
    integration_method = IntegrationMethod.TRANSPARENT_REDIRECT

class TransactionProcessor:

    def __init__(self, merchant_id=None, merchant_password=None, request_gateway_entry_points=None, currency_code=None, amount=None, order_id=None, order_description=None, customer_name=None, address1=None, address2=None, address3=None, address4=None, city=None, state=None, postcode=None, country_code=None, email_address=None, phone_number=None, ip_address=None, transaction_type=TransactionType.SALE, cv2=None, override_cv2=False, cv2_override_policy=None, override_avs=False, avs_override_policy=None):
        self.merchant_id = merchant_id
        self.merchant_password = merchant_password
        self.request_gateway_entry_points = request_gateway_entry_points
        self.currency_code = currency_code
        self.amount = None if amount is None else str(amount)
        self.order_id = None if order_id is None else str(order_id)
        self.order_description = order_description
        self.customer_name = customer_name
        self.address1 = address1
        self.address2 = address2
        self.address3 = address3
        self.address4 = address4
        self.city = city
        self.state = state
        self.postcode = postcode
        self.country_code = country_code
        self.email_address = email_address
        self.phone_number = phone_number
        self.ip_address = ip_address
        self.transaction_type = transaction_type
        self.cv2 = None if cv2 is None else str(cv2)
        self.override_cv2 = override_cv2
        self.cv2_override_policy = cv2_override_policy
        self.override_avs = override_avs
        self.avs_override_policy = avs_override_policy
        self.java_enabled = self.java_script_enabled = self.screen_width = self.screen_height = self.screen_colour_depth = self.timezone_offset = self.language = self.challenge_notification_url = self.fingerprint_notification_url = None

    def _attach_entry_points(self, tx):
        if self.request_gateway_entry_points is not None:
            tx.request_gateway_entry_points = self.request_gateway_entry_points
        return tx

    def _set_shared_fields(self, tx, user_agent='*/*'):
        tx.merchant_authentication.merchant_id = self.merchant_id
        tx.merchant_authentication.password = self.merchant_password
        d = tx.transaction_details
        d.message_details.transaction_type = self.transaction_type
        c = d.transaction_control
        c.echo_card_type = True
        c.echo_amount_received = True
        c.echo_avs_check_result = True
        c.echo_cv2_check_result = True
        c.duplicate_delay = 60
        b = d.three_d_secure_browser_details
        b.device_category = 0
        b.accept_headers = '*/*'
        b.user_agent = user_agent or '*/*'
        b.java_enabled = self.java_enabled
        b.java_script_enabled = self.java_script_enabled
        b.screen_width = self.screen_width
        b.screen_height = self.screen_height
        b.screen_colour_depth = self.screen_colour_depth
        b.time_zone = self.timezone_offset
        b.language = self.language
        n = d.three_d_secure_notification_details
        n.challenge_notification_url = self.challenge_notification_url
        n.fingerprint_notification_url = self.fingerprint_notification_url
        d.currency_code = self.currency_code
        d.amount = self.amount
        d.order_id = self.order_id
        d.order_description = self.order_description
        if self.override_avs:
            c.avs_override_policy = self.avs_override_policy
        if self.override_cv2:
            c.cv2_override_policy = self.cv2_override_policy
        a = tx.customer_details.billing_address
        a.address1 = self.address1
        a.address2 = self.address2
        a.address3 = self.address3
        a.address4 = self.address4
        a.city = self.city
        a.state = self.state
        a.post_code = self.postcode
        a.country_code = self.country_code
        tx.customer_details.email_address = self.email_address
        tx.customer_details.phone_number = self.phone_number
        tx.customer_details.customer_ip_address = self.ip_address
        return tx

    def get_hosted_payment_form(self, callback_url, server_result_url, pre_shared_key, hash_method, result_delivery_method, payment_form_displays_result, session_handler):
        session_handler.set_session_value('payvector_transaction_is_cross_reference', False)
        dt = datetime.now(timezone.utc).astimezone().strftime('%Y-%m-%d %H:%M:%S %z')
        dt = dt[:-2] + ':' + dt[-2:]
        s = PaymentFormHelper.generate_string_to_hash(self.merchant_id, self.merchant_password, self.amount, self.currency_code, self.order_id, self.transaction_type, dt, callback_url, self.order_description or '', self.customer_name or '', self.address1 or '', self.address2 or '', self.address3 or '', self.address4 or '', self.city or '', self.state or '', self.postcode or '', self.country_code or '', 'true', 'false', 'false', 'false', 'false', 'false', result_delivery_method, server_result_url, PaymentFormHelper.bool_to_string(payment_form_displays_result), pre_shared_key, hash_method, self.email_address or '', self.phone_number or '')
        return {'HashDigest': PaymentFormHelper.calculate_hash_digest(s, pre_shared_key, hash_method), 'MerchantID': self.merchant_id, 'Amount': str(self.amount), 'CurrencyCode': str(self.currency_code), 'EchoAVSCheckResult': 'true', 'EchoCV2CheckResult': 'true', 'EchoThreeDSecureAuthenticationCheckResult': 'true', 'EchoCardType': 'true', 'EchoCardNumberFirstSix': 'true', 'EchoCardNumberLastFour': 'true', 'OrderID': str(self.order_id), 'TransactionType': self.transaction_type, 'TransactionDateTime': dt, 'CallbackURL': callback_url, 'OrderDescription': self.order_description or '', 'CustomerName': self.customer_name or '', 'Address1': self.address1 or '', 'Address2': self.address2 or '', 'Address3': self.address3 or '', 'Address4': self.address4 or '', 'City': self.city or '', 'State': self.state or '', 'Postcode': self.postcode or '', 'CountryCode': str(self.country_code or ''), 'EmailAddress': self.email_address or '', 'PhoneNumber': self.phone_number or '', 'CV2Mandatory': 'true', 'Address1Mandatory': 'false', 'CityMandatory': 'false', 'PostCodeMandatory': 'false', 'StateMandatory': 'false', 'CountryMandatory': 'false', 'ResultDeliveryMethod': result_delivery_method, 'ServerResultURL': server_result_url, 'PaymentFormDisplaysResult': PaymentFormHelper.bool_to_string(payment_form_displays_result), 'ServerResultURLCookieVariables': '', 'ServerResultURLFormVariables': '', 'ServerResultURLQueryStringVariables': ''}

    def get_transparent_form(self, callback_url, pre_shared_key, hash_method, session_handler):
        session_handler.set_session_value('payvector_transaction_is_cross_reference', False)
        dt = datetime.now(timezone.utc).astimezone().strftime('%Y-%m-%d %H:%M:%S %z')
        dt = dt[:-2] + ':' + dt[-2:]
        s = PaymentFormHelper.generate_string_to_hash_initial(hash_method, pre_shared_key, self.merchant_id, self.merchant_password, self.amount, self.currency_code, self.order_id, self.transaction_type, dt, callback_url, self.order_description)
        return {'HashDigest': PaymentFormHelper.calculate_hash_digest(s, pre_shared_key, hash_method), 'MerchantID': self.merchant_id, 'Amount': str(self.amount), 'CurrencyCode': str(self.currency_code), 'OrderID': str(self.order_id), 'TransactionType': self.transaction_type, 'TransactionDateTime': dt, 'CallbackURL': callback_url, 'OrderDescription': self.order_description, 'Address1': self.address1, 'Address2': self.address2, 'Address3': self.address3, 'Address4': self.address4, 'City': self.city, 'State': self.state, 'Postcode': self.postcode, 'CountryCode': str(self.country_code), 'EmailAddress': self.email_address, 'PhoneNumber': self.phone_number}

    def do_card_details_transaction(self, card_number, expiry_date_month, expiry_date_year, issue_number, session_handler, user_agent='*/*'):
        session_handler.set_session_value('payvector_transaction_is_cross_reference', False)
        tx = self._set_shared_fields(self._attach_entry_points(CardDetailsTransaction()), user_agent)
        tx.card_details.card_name = self.customer_name
        tx.card_details.card_number = str(card_number)
        tx.card_details.expiry_date.month = expiry_date_month or None
        tx.card_details.expiry_date.year = str(expiry_date_year)[-2:] if expiry_date_year not in (None, '') else None
        tx.card_details.issue_number = issue_number
        tx.card_details.cv2 = self.cv2
        ok = tx.process_transaction()
        return CardDetailsFinalTransactionResult(ok, tx, tx.card_details_transaction_result, tx.transaction_output_data, session_handler)

    def do_cross_reference_transaction(self, cross_reference, three_d_secure_override_policy, session_handler, user_agent='*/*'):
        session_handler.set_session_value('payvector_transaction_is_cross_reference', True)
        tx = self._set_shared_fields(self._attach_entry_points(CrossReferenceTransaction()), user_agent)
        tx.transaction_details.message_details.cross_reference = cross_reference
        tx.override_card_details.cv2 = self.cv2
        tx.transaction_details.transaction_control.three_d_secure_override_policy = three_d_secure_override_policy
        ok = tx.process_transaction()
        return CrossReferenceFinalTransactionResult(ok, tx, tx.cross_reference_transaction_result, tx.transaction_output_data, session_handler)

    def check3_d_secure_result(self, cross_reference, pa_res, session_handler):
        tx = self._attach_entry_points(ThreeDSecureAuthentication())
        tx.merchant_authentication.merchant_id = self.merchant_id
        tx.merchant_authentication.password = self.merchant_password
        tx.three_d_secure_input_data.cross_reference = cross_reference
        tx.three_d_secure_input_data.pa_res = pa_res
        ok = tx.process_transaction()
        return ThreeDSecureFinalTransactionResult(ok, tx, tx.three_d_secure_authentication_result, tx.transaction_output_data, session_handler)
