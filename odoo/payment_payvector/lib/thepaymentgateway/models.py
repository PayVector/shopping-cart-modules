
from __future__ import annotations
from datetime import datetime
from typing import Any, Iterator
import re


def _snake(name: str) -> str:
    s1 = re.sub(r"(.)([A-Z][a-z]+)", r"\1_\2", name)
    return re.sub(r"([a-z0-9])([A-Z])", r"\1_\2", s1).lower()

WIRE_NAMES = {
    'accept_headers': 'AcceptHeaders',
    'account_number': 'AccountNumber',
    'acs_url': 'ACSURL',
    'address1': 'Address1',
    'address2': 'Address2',
    'address3': 'Address3',
    'address4': 'Address4',
    'address_details': 'AddressDetails',
    'address_numeric_check_result': 'AddressNumericCheckResult',
    'agency_address': 'AgencyAddress',
    'agency_code': 'AgencyCode',
    'agency_name': 'AgencyName',
    'airline_details': 'AirlineDetails',
    'airline_invoice_number': 'AirlineInvoiceNumber',
    'airline_plan_number': 'AirlinePlanNumber',
    'alternate_payment_data': 'AlternatePaymentData',
    'amount': 'Amount',
    'amount_received': 'AmountReceived',
    'application_identifier': 'ApplicationIdentifier',
    'application_interchange_profile': 'ApplicationInterchangeProfile',
    'application_pan_sequence_number': 'ApplicationPANSequenceNumber',
    'application_transaction_counter': 'ApplicationTransactionCounter',
    'application_usage_control': 'ApplicationUsageControl',
    'arrival_airport_code': 'ArrivalAirportCode',
    'auth_code': 'AuthCode',
    'authentication_status': 'AuthenticationStatus',
    'authentication_value': 'AuthenticationValue',
    'authorisation_attempted': 'AuthorisationAttempted',
    'authorisation_request_cryptogram': 'AuthorisationRequestCryptogram',
    'authorisation_response_code': 'AuthorisationResponseCode',
    'avs_override_policy': 'AVSOverridePolicy',
    'billing_address': 'BillingAddress',
    'business_address': 'BusinessAddress',
    'card_class': 'CardClass',
    'card_name': 'CardName',
    'card_number': 'CardNumber',
    'card_type': 'CardType',
    'card_type_data': 'CardTypeData',
    'cardholder_verification_method_results': 'CardholderVerificationMethodResults',
    'carrier_code': 'CarrierCode',
    'carrier_name': 'CarrierName',
    'challenge_notification_url': 'ChallengeNotificationURL',
    'city': 'City',
    'country_code': 'CountryCode',
    'creq': 'CREQ',
    'cres': 'CRES',
    'cross_reference': 'CrossReference',
    'cryptogram_information_data': 'CryptogramInformationData',
    'cryptogram_transaction_type': 'CryptogramTransactionType',
    'currency_code': 'CurrencyCode',
    'custom_variables': 'CustomVariables',
    'customer_ip_address': 'CustomerIPAddress',
    'cv2': 'CV2',
    'cv2_check_result': 'CV2CheckResult',
    'cv2_override_policy': 'CV2OverridePolicy',
    'date_of_birth': 'DateOfBirth',
    'departure_airport_code': 'DepartureAirportCode',
    'departure_tax_amount': 'DepartureTaxAmount',
    'description': 'Description',
    'device_capture_id': 'DeviceCaptureID',
    'device_category': 'DeviceCategory',
    'duplicate_delay': 'DuplicateDelay',
    'dynamic_descriptor': 'DynamicDescriptor',
    'echo_amount_received': 'EchoAmountReceived',
    'echo_avs_check_result': 'EchoAVSCheckResult',
    'echo_card_expiry_date': 'EchoCardExpiryDate',
    'echo_card_number_first_six': 'EchoCardNumberFirstSix',
    'echo_card_number_last_four': 'EchoCardNumberLastFour',
    'echo_card_type': 'EchoCardType',
    'echo_cv2_check_result': 'EchoCV2CheckResult',
    'echo_external_data': 'EchoExternalData',
    'echo_fraud_protection_check_result': 'EchoFraudProtectionCheckResult',
    'echo_three_d_secure_authentication_check_result': 'EchoThreeDSecureAuthenticationCheckResult',
    'eci': 'ECI',
    'email_address': 'EmailAddress',
    'emv_terminal_type': 'EMVTerminalType',
    'enrolment_status': 'EnrolmentStatus',
    'entry_point_url': 'EntryPointURL',
    'error_messages': 'ErrorMessages',
    'exemption_indicator': 'ExemptionIndicator',
    'exemption_reason': 'ExemptionReason',
    'exemption_type': 'ExemptionType',
    'expiry_date': 'ExpiryDate',
    'external_client_reference': 'ExternalClientReference',
    'external_cross_reference': 'ExternalCrossReference',
    'external_message': 'ExternalMessage',
    'external_response_code': 'ExternalResponseCode',
    'external_transaction_uid': 'ExternalTransactionUID',
    'fare_basis_code': 'FareBasisCode',
    'fingerprint_notification_url': 'FingerprintNotificationURL',
    'flight_legs': 'FlightLegs',
    'flight_number': 'FlightNumber',
    'fraud_protection_check_result': 'FraudProtectionCheckResult',
    'fraud_protection_reason_code': 'FraudProtectionReasonCode',
    'gateway_entry_points': 'GatewayEntryPoints',
    'icc_details': 'ICCDetails',
    'iso_code': 'ISOCode',
    'issue_number': 'IssueNumber',
    'issue_number_status': 'IssueNumberStatus',
    'issuer': 'Issuer',
    'issuer_application_data': 'IssuerApplicationData',
    'java_enabled': 'JavaEnabled',
    'java_script_enabled': 'JavaScriptEnabled',
    'language': 'Language',
    'leg_number': 'LegNumber',
    'line_item_details': 'LineItemDetails',
    'line_items': 'LineItems',
    'luhn_check_required': 'LuhnCheckRequired',
    'merchant_id': 'MerchantID',
    'merchant_identifier': 'MerchantIdentifier',
    'merchant_name': 'MerchantName',
    'message': 'Message',
    'message_details': 'MessageDetails',
    'method': 'Method',
    'method_data': 'MethodData',
    'method_specific_fields': 'MethodSpecificFields',
    'method_url': 'MethodURL',
    'metric': 'Metric',
    'month': 'Month',
    'name': 'Name',
    'new_transaction': 'NewTransaction',
    'order_description': 'OrderDescription',
    'order_id': 'OrderID',
    'other_card_data': 'OtherCardData',
    'pa_req': 'PaREQ',
    'pa_res': 'PaRES',
    'passenger_details': 'PassengerDetails',
    'passenger_name': 'PassengerName',
    'passenger_reference': 'PassengerReference',
    'password': 'Password',
    'payment_link_uid': 'PaymentLinkUID',
    'phone_number': 'PhoneNumber',
    'pos_entry_mode': 'POSEntryMode',
    'post_code': 'PostCode',
    'post_code_check_result': 'PostCodeCheckResult',
    'previous_transaction_result': 'PreviousTransactionResult',
    'primary_account_details': 'PrimaryAccountDetails',
    'quantity': 'Quantity',
    'retry_attempts': 'RetryAttempts',
    'sales_tax_amount': 'SalesTaxAmount',
    'sales_tax_description': 'SalesTaxDescription',
    'scheme_transaction_id': 'SchemeTransactionID',
    'screen_colour_depth': 'ScreenColourDepth',
    'screen_height': 'ScreenHeight',
    'screen_width': 'ScreenWidth',
    'shipping_address': 'ShippingAddress',
    'shipping_details': 'ShippingDetails',
    'sred': 'SRED',
    'sred_ksn': 'SREDKSN',
    'start_date': 'StartDate',
    'start_date_status': 'StartDateStatus',
    'state': 'State',
    'status_code': 'StatusCode',
    'stop_over_code': 'StopOverCode',
    'terminal_application_version_number': 'TerminalApplicationVersionNumber',
    'terminal_capabilities': 'TerminalCapabilities',
    'terminal_country_code': 'TerminalCountryCode',
    'terminal_transaction_date': 'TerminalTransactionDate',
    'terminal_verification_result': 'TerminalVerificationResult',
    'three_d_secure_authentication_check_result': 'ThreeDSecureAuthenticationCheckResult',
    'three_d_secure_authentication_exemption': 'ThreeDSecureAuthenticationExemption',
    'three_d_secure_browser_details': 'ThreeDSecureBrowserDetails',
    'three_d_secure_notification_details': 'ThreeDSecureNotificationDetails',
    'three_d_secure_output_data': 'ThreeDSecureOutputData',
    'three_d_secure_override_policy': 'ThreeDSecureOverridePolicy',
    'three_d_secure_passthrough_data': 'ThreeDSecurePassthroughData',
    'three_d_secure_version': 'ThreeDSecureVersion',
    'ticket_number': 'TicketNumber',
    'time_zone': 'TimeZone',
    'transaction_control': 'TransactionControl',
    'transaction_cryptogram': 'TransactionCryptogram',
    'transaction_identifier': 'TransactionIdentifier',
    'transaction_status_information': 'TransactionStatusInformation',
    'transaction_type': 'TransactionType',
    'travel_agency_details': 'TravelAgencyDetails',
    'travel_class': 'TravelClass',
    'travel_date': 'TravelDate',
    'unpredictable_number': 'UnpredictableNumber',
    'user_agent': 'UserAgent',
    'value': 'Value',
    'year': 'Year'
}

class Model:
    _fields: tuple[str, ...] = ()

    def __init__(self, **kwargs: Any):
        unknown = set(kwargs) - set(self._fields)
        if unknown:
            names = ", ".join(sorted(unknown))
            raise TypeError(f"Unexpected field(s) for {self.__class__.__name__}: {names}")
        for field in self._fields:
            object.__setattr__(self, field, self._default_for(field))
        for key, value in kwargs.items():
            object.__setattr__(self, key, value)

    def _default_for(self, field: str) -> Any:
        return None

    def __repr__(self) -> str:
        values = ", ".join(
            f"{field}={getattr(self, field)!r}"
            for field in self._fields
            if getattr(self, field, None) is not None
        )
        return f"{self.__class__.__name__}({values})"

class ModelList(Model):
    _item_type = Model
    def __init__(self):
        self._items: list[Any] = []
    @property
    def count(self) -> int: return len(self._items)
    def __len__(self): return len(self._items)
    def __iter__(self) -> Iterator[Any]: return iter(self._items)
    def __getitem__(self, key):
        if isinstance(key, str):
            for x in self._items:
                if getattr(x,'name',None) and getattr(x,'name').lower()==key.lower(): return x
            return None
        return self._items[key]
    def add(self, item: Any): self._items.append(item); return item


class Variable(Model):
    _fields=("name","value")
class Field(Model):
    _fields=("name","value")

class CustomVariables(ModelList):
    _item_type=Variable
    def add(self, name, value): return ModelList.add(self, Variable(name=name, value=value))
class MethodSpecificFields(ModelList):
    _item_type=Field
    def add(self, name, value): return ModelList.add(self, Field(name=name, value=value))

class LineItems(ModelList):
    pass
class FlightLegs(ModelList):
    pass
class GatewayEntryPointList(ModelList):
    pass

class CustomerDetails(Model):
    _fields=('billing_address', 'primary_account_details', 'shipping_details', 'email_address', 'phone_number', 'customer_ip_address', 'date_of_birth')
    def _default_for(self, field):
        if field == 'billing_address': return BillingAddress()
        if field == 'primary_account_details': return PrimaryAccountDetails()
        if field == 'shipping_details': return ShippingDetails()
        return None

class MerchantDetails(Model):
    _fields=('business_address', 'merchant_name', 'merchant_identifier', 'email_address', 'phone_number')
    def _default_for(self, field):
        if field == 'business_address': return BusinessAddress()
        return None

class ShippingDetails(Model):
    _fields=('shipping_address', 'name', 'email_address', 'phone_number')
    def _default_for(self, field):
        if field == 'shipping_address': return ShippingAddress()
        return None

class ShippingAddress(Model):
    _fields=('address1', 'address2', 'address3', 'address4', 'city', 'state', 'post_code', 'country_code')

class BillingAddress(Model):
    _fields=('address1', 'address2', 'address3', 'address4', 'city', 'state', 'post_code', 'country_code')

class BusinessAddress(Model):
    _fields=('address1', 'address2', 'address3', 'address4', 'city', 'state', 'post_code', 'country_code')

class AddressDetails(Model):
    _fields=('address1', 'address2', 'address3', 'address4', 'city', 'state', 'post_code', 'country_code')

class PrimaryAccountDetails(Model):
    _fields=('address_details', 'name', 'account_number', 'date_of_birth')
    def _default_for(self, field):
        if field == 'address_details': return AddressDetails()
        return None

class LineItemDetails(Model):
    _fields=('sales_tax_amount', 'sales_tax_description', 'line_items')
    def _default_for(self, field):
        if field == 'line_items': return LineItems()
        return None

class LineItem(Model):
    _fields=('quantity', 'amount', 'description')

class AirlineDetails(Model):
    _fields=('carrier_name', 'airline_plan_number', 'airline_invoice_number', 'travel_agency_details', 'passenger_details', 'flight_legs')
    def _default_for(self, field):
        if field == 'travel_agency_details': return TravelAgencyDetails()
        if field == 'passenger_details': return PassengerDetails()
        if field == 'flight_legs': return FlightLegs()
        return None

class TravelAgencyDetails(Model):
    _fields=('agency_name', 'agency_code', 'agency_address')
    def _default_for(self, field):
        if field == 'agency_address': return AgencyAddress()
        return None

class AgencyAddress(Model):
    _fields=('address1', 'address2', 'address3', 'address4', 'city', 'state', 'post_code', 'country_code')

class PassengerDetails(Model):
    _fields=('ticket_number', 'passenger_name', 'passenger_reference')

class FlightLeg(Model):
    _fields=('leg_number', 'departure_airport_code', 'arrival_airport_code', 'stop_over_code', 'travel_class', 'carrier_code', 'departure_tax_amount', 'travel_date', 'fare_basis_code', 'flight_number')

class CreditCardDate(Model):
    _fields=('month', 'year')

class ExpiryDate(Model):
    _fields=('month', 'year')

class StartDate(Model):
    _fields=('month', 'year')

class CardDetails(Model):
    _fields=('card_name', 'card_number', 'expiry_date', 'start_date', 'issue_number', 'cv2', 'sred', 'sred_ksn', 'icc_details')
    def _default_for(self, field):
        if field == 'expiry_date': return ExpiryDate()
        if field == 'start_date': return StartDate()
        if field == 'icc_details': return ICCDetails()
        return None

class ICCDetails(Model):
    _fields=('application_pan_sequence_number', 'cryptogram_transaction_type', 'terminal_transaction_date', 'terminal_country_code', 'authorisation_request_cryptogram', 'transaction_cryptogram', 'application_interchange_profile', 'application_transaction_counter', 'unpredictable_number', 'terminal_verification_result', 'issuer_application_data', 'application_usage_control', 'cryptogram_information_data', 'cardholder_verification_method_results', 'application_identifier', 'terminal_application_version_number', 'transaction_status_information', 'emv_terminal_type', 'terminal_capabilities', 'pos_entry_mode', 'other_card_data', 'authorisation_response_code')

class OverrideCardDetails(Model):
    _fields=('card_name', 'card_number', 'expiry_date', 'start_date', 'issue_number', 'cv2', 'sred', 'sred_ksn', 'icc_details')
    def _default_for(self, field):
        if field == 'expiry_date': return ExpiryDate()
        if field == 'start_date': return StartDate()
        if field == 'icc_details': return ICCDetails()
        return None

class MerchantAuthentication(Model):
    _fields=('merchant_id', 'password')

class MessageDetails(Model):
    _fields=('transaction_type', 'new_transaction', 'cross_reference', 'payment_link_uid', 'alternate_payment_data')
    def _default_for(self, field):
        if field == 'alternate_payment_data': return AlternatePaymentData()
        return None

class AlternatePaymentData(Model):
    _fields=('method', 'method_specific_fields')
    def _default_for(self, field):
        if field == 'method_specific_fields': return MethodSpecificFields()
        return None

class TransactionDetails(Model):
    _fields=('message_details', 'amount', 'currency_code', 'order_id', 'order_description', 'dynamic_descriptor', 'device_capture_id', 'line_item_details', 'transaction_control', 'three_d_secure_browser_details', 'three_d_secure_passthrough_data', 'three_d_secure_notification_details', 'three_d_secure_authentication_exemption', 'airline_details')
    def _default_for(self, field):
        if field == 'message_details': return MessageDetails()
        if field == 'line_item_details': return LineItemDetails()
        if field == 'transaction_control': return TransactionControl()
        if field == 'three_d_secure_browser_details': return ThreeDSecureBrowserDetails()
        if field == 'three_d_secure_passthrough_data': return ThreeDSecurePassthroughData()
        if field == 'three_d_secure_notification_details': return ThreeDSecureNotificationDetails()
        if field == 'three_d_secure_authentication_exemption': return ThreeDSecureAuthenticationExemption()
        if field == 'airline_details': return AirlineDetails()
        return None

class ThreeDSecureBrowserDetails(Model):
    _fields=('device_category', 'accept_headers', 'user_agent', 'language', 'screen_height', 'screen_width', 'screen_colour_depth', 'time_zone', 'java_enabled', 'java_script_enabled')

class TransactionControl(Model):
    _fields=('echo_card_type', 'echo_avs_check_result', 'echo_cv2_check_result', 'echo_amount_received', 'echo_external_data', 'echo_fraud_protection_check_result', 'echo_three_d_secure_authentication_check_result', 'echo_card_number_first_six', 'echo_card_number_last_four', 'echo_card_expiry_date', 'duplicate_delay', 'avs_override_policy', 'cv2_override_policy', 'three_d_secure_override_policy', 'auth_code', 'custom_variables')
    def _default_for(self, field):
        if field == 'custom_variables': return CustomVariables()
        return None

class ThreeDSecureInputData(Model):
    _fields=('cross_reference', 'pa_res', 'cres')

class ThreeDSecureEnvironmentData(Model):
    _fields=('cross_reference', 'method_data')

class ThreeDSecurePassthroughData(Model):
    _fields=('enrolment_status', 'authentication_status', 'eci', 'authentication_value', 'transaction_identifier', 'three_d_secure_version')

class ThreeDSecureNotificationDetails(Model):
    _fields=('fingerprint_notification_url', 'challenge_notification_url')

class ThreeDSecureAuthenticationExemption(Model):
    _fields=('exemption_type', 'exemption_reason', 'exemption_indicator')

class Issuer(Model):
    _fields=('name', 'iso_code')
    def __init__(self, name=None, iso_code=None, **kwargs): super().__init__(name=name, iso_code=iso_code, **kwargs)

class CardTypeData(Model):
    _fields=('card_type', 'card_class', 'issuer', 'luhn_check_required', 'issue_number_status', 'start_date_status')
    def __init__(self, card_type=None, card_class=None, issuer=None, luhn_check_required=None, issue_number_status=None, start_date_status=None, **kwargs):
        super().__init__(card_type=card_type, card_class=card_class, issuer=issuer, luhn_check_required=luhn_check_required, issue_number_status=issue_number_status, start_date_status=start_date_status, **kwargs)

class GatewayEntryPoint(Model):
    _fields=('entry_point_url', 'metric')
    def __init__(self, entry_point_url=None, metric=0, **kwargs):
        super().__init__(entry_point_url=entry_point_url, metric=metric, **kwargs)

class PreviousTransactionResult(Model):
    _fields=('status_code', 'message')
    def __init__(self, status_code=None, message=None, **kwargs): super().__init__(status_code=status_code, message=message, **kwargs)

class GatewayOutput(Model):
    _fields=('status_code', 'message', 'error_messages')
    def __init__(self, status_code=0, message=None, error_messages=None, **kwargs): super().__init__(status_code=status_code, message=message, error_messages=error_messages or [], **kwargs)

class GetGatewayEntryPointsResult(Model):
    _fields=('status_code', 'message', 'error_messages')

class CreatePayByLinkResult(Model):
    _fields=('status_code', 'message', 'error_messages')

class GetCardTypeResult(Model):
    _fields=('status_code', 'message', 'error_messages')

class PaymentMessageGatewayOutput(Model):
    _fields=('status_code', 'message', 'error_messages', 'previous_transaction_result', 'authorisation_attempted')

class CardDetailsTransactionResult(Model):
    _fields=('status_code', 'message', 'error_messages', 'previous_transaction_result', 'authorisation_attempted')

class CrossReferenceTransactionResult(Model):
    _fields=('status_code', 'message', 'error_messages', 'previous_transaction_result', 'authorisation_attempted')

class ThreeDSecureAuthenticationResult(Model):
    _fields=('status_code', 'message', 'error_messages', 'previous_transaction_result', 'authorisation_attempted')

class ThreeDSecureEnvironmentResult(Model):
    _fields=('status_code', 'message', 'error_messages', 'previous_transaction_result', 'authorisation_attempted')

class ThreeDSecureOutputData(Model):
    _fields=('pa_req', 'acs_url', 'three_d_secure_version', 'creq', 'method_data', 'method_url')

class GetGatewayEntryPointsOutputData(Model):
    _fields=('gateway_entry_points',)

class TransactionOutputData(Model):
    _fields=('gateway_entry_points', 'cross_reference', 'external_client_reference', 'external_cross_reference', 'external_transaction_uid', 'external_response_code', 'external_message', 'auth_code', 'address_numeric_check_result', 'post_code_check_result', 'three_d_secure_authentication_check_result', 'cv2_check_result', 'fraud_protection_check_result', 'fraud_protection_reason_code', 'scheme_transaction_id', 'card_type_data', 'amount_received', 'three_d_secure_output_data', 'custom_variables')

class GetCardTypeOutputData(Model):
    _fields=('gateway_entry_points', 'card_type_data')

class CreatePayByLinkOutputData(Model):
    _fields=('gateway_entry_points', 'payment_link_uid', 'custom_variables')

class BaseOutputData(Model):
    _fields=('gateway_entry_points',)


class RequestGatewayEntryPoint(GatewayEntryPoint):
    _fields=("entry_point_url","metric","retry_attempts")
    def __init__(self, entry_point_url=None, metric=0, retry_attempts=1, **kwargs):
        Model.__init__(self, entry_point_url=entry_point_url, metric=metric, retry_attempts=retry_attempts, **kwargs)

class RequestGatewayEntryPointList(ModelList):
    _item_type=RequestGatewayEntryPoint
    def add(self, entry_point_url, metric, retry_attempts):
        return ModelList.add(self, RequestGatewayEntryPoint(entry_point_url, metric, retry_attempts))
    def sort(self, comparer=None): self._items.sort(key=lambda x: x.metric)

# Python-native nullable aliases retained for discoverability/porting documentation.
NullableInt32 = int | None
NullableInt64 = int | None
NullableUInt32 = int | None
NullableUInt64 = int | None
NullableDouble = float | None
NullableBool = bool | None
NullableDateTime = datetime | None

# three_d_secure_override_policy is nullable and defaults to None.
