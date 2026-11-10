
from __future__ import annotations
import xml.etree.ElementTree as ET
from datetime import date, datetime
from .models import Model, ModelList, WIRE_NAMES

SOAP_ENV="http://schemas.xmlsoap.org/soap/envelope/"
METHOD_URI="https://www.thepaymentgateway.net/"
ET.register_namespace("soap", SOAP_ENV)

def _fmt(v):
    if isinstance(v,bool): return "True" if v else "False"
    if isinstance(v,datetime): return v.strftime("%Y-%m-%d")
    if isinstance(v,date): return v.strftime("%Y-%m-%d")
    return str(v)

def _norm(path):
    import re
    return re.sub(r"\[\d+\]", "[]", path)

def _wire_path(path):
    parts = []
    for part in path.split("."):
        suffix = "[]" if part.endswith("[]") else ""
        base = part[:-2] if suffix else part
        parts.append(WIRE_NAMES.get(base, base) + suffix)
    return ".".join(parts)

def _append(parent, obj, path, attr_fields):
    # returns whether anything was emitted into parent
    emitted=False
    if obj is None: return False
    if isinstance(obj, ModelList):
        return False
    for field in getattr(obj,'_fields',()):
        value=getattr(obj,field,None)
        if value is None or value == "": continue
        xml_field = WIRE_NAMES.get(field, field)
        full = f"{path}.{xml_field}" if path else xml_field
        if _norm(full) in attr_fields:
            parent.set(xml_field,_fmt(value)); emitted=True; continue
        if isinstance(value, ModelList):
            if len(value)==0: continue
            container=ET.Element(xml_field)
            item_name={"line_items":"LineItem","flight_legs":"FlightLeg","method_specific_fields":"Field","custom_variables":"Variable","gateway_entry_points":"GatewayEntryPoint"}.get(field)
            if not item_name: continue
            for idx,item in enumerate(value):
                ch=ET.Element(item_name)
                if _append(ch,item,f"{full}.{item_name}[{idx}]",attr_fields): container.append(ch)
            if len(container): parent.append(container); emitted=True
        elif isinstance(value, Model):
            ch=ET.Element(xml_field)
            if _append(ch,value,full,attr_fields) or ch.attrib:
                parent.append(ch); emitted=True
        else:
            ch=ET.SubElement(parent,xml_field); ch.text=_fmt(value); emitted=True
    return emitted

def build_envelope(method, message_name, objects, merchant_auth, attr_fields):
    env=ET.Element(f"{{{SOAP_ENV}}}Envelope")
    body=ET.SubElement(env,f"{{{SOAP_ENV}}}Body")
    method_el=ET.SubElement(body,method); method_el.set("xmlns",METHOD_URI)
    msg=ET.SubElement(method_el,message_name)
    if merchant_auth is not None:
        ma=ET.Element("MerchantAuthentication")
        if getattr(merchant_auth,"merchant_id",None): ma.set("MerchantID",str(merchant_auth.merchant_id))
        if getattr(merchant_auth,"password",None): ma.set("Password",str(merchant_auth.password))
        if ma.attrib: msg.append(ma)
    for xml_name,obj in objects:
        if obj is None: continue
        el=ET.Element(xml_name)
        if _append(el,obj,f"{message_name}.{xml_name}",attr_fields) or el.attrib:
            msg.append(el)
    data=ET.tostring(env,encoding="utf-8",xml_declaration=True)
    return data.decode("utf-8")

def lname(tag): return tag.split('}',1)[-1]
def child(node,name):
    if node is None:return None
    wire_name = WIRE_NAMES.get(name, name)
    for c in node:
        if lname(c.tag)==wire_name:return c
    return None
def children(node,name):
    if node is None:return []
    wire_name = WIRE_NAMES.get(name, name)
    return [c for c in node if lname(c.tag)==wire_name]
def text(node,name,default=None):
    c=child(node,name); return default if c is None or c.text is None else c.text
def as_int(v,default=None):
    try:return int(v)
    except:return default
def as_bool(v,default=None):
    if v is None:return default
    return str(v).strip().lower() in ('true','1','yes')

ATTR_FIELDS = {'CardDetailsTransaction': ['PaymentMessage.CardDetails.expiry_date.month', 'PaymentMessage.CardDetails.expiry_date.year', 'PaymentMessage.CardDetails.icc_details.application_identifier', 'PaymentMessage.CardDetails.icc_details.application_interchange_profile', 'PaymentMessage.CardDetails.icc_details.application_pan_sequence_number', 'PaymentMessage.CardDetails.icc_details.application_transaction_counter', 'PaymentMessage.CardDetails.icc_details.application_usage_control', 'PaymentMessage.CardDetails.icc_details.authorisation_request_cryptogram', 'PaymentMessage.CardDetails.icc_details.authorisation_response_code', 'PaymentMessage.CardDetails.icc_details.cardholder_verification_method_results', 'PaymentMessage.CardDetails.icc_details.cryptogram_information_data', 'PaymentMessage.CardDetails.icc_details.cryptogram_transaction_type', 'PaymentMessage.CardDetails.icc_details.emv_terminal_type', 'PaymentMessage.CardDetails.icc_details.issuer_application_data', 'PaymentMessage.CardDetails.icc_details.other_card_data', 'PaymentMessage.CardDetails.icc_details.pos_entry_mode', 'PaymentMessage.CardDetails.icc_details.terminal_application_version_number', 'PaymentMessage.CardDetails.icc_details.terminal_capabilities', 'PaymentMessage.CardDetails.icc_details.terminal_country_code', 'PaymentMessage.CardDetails.icc_details.terminal_transaction_date', 'PaymentMessage.CardDetails.icc_details.terminal_verification_result', 'PaymentMessage.CardDetails.icc_details.transaction_cryptogram', 'PaymentMessage.CardDetails.icc_details.transaction_status_information', 'PaymentMessage.CardDetails.icc_details.unpredictable_number', 'PaymentMessage.CardDetails.sred', 'PaymentMessage.CardDetails.sred_ksn', 'PaymentMessage.CardDetails.start_date.month', 'PaymentMessage.CardDetails.start_date.year', 'PaymentMessage.MerchantDetails.merchant_identifier', 'PaymentMessage.TransactionDetails.airline_details.airline_invoice_number', 'PaymentMessage.TransactionDetails.airline_details.airline_plan_number', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].arrival_airport_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].carrier_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].departure_airport_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].departure_tax_amount', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].fare_basis_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].flight_number', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].leg_number', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].stop_over_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].travel_class', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].travel_date', 'PaymentMessage.TransactionDetails.airline_details.passenger_details.ticket_number', 'PaymentMessage.TransactionDetails.airline_details.travel_agency_details.agency_code', 'PaymentMessage.TransactionDetails.amount', 'PaymentMessage.TransactionDetails.currency_code', 'PaymentMessage.TransactionDetails.device_capture_id', 'PaymentMessage.TransactionDetails.line_item_details.line_items.LineItem[].amount', 'PaymentMessage.TransactionDetails.line_item_details.line_items.LineItem[].quantity', 'PaymentMessage.TransactionDetails.line_item_details.sales_tax_amount', 'PaymentMessage.TransactionDetails.message_details.alternate_payment_data.method', 'PaymentMessage.TransactionDetails.message_details.alternate_payment_data.method_specific_fields.Field[].name', 'PaymentMessage.TransactionDetails.message_details.alternate_payment_data.method_specific_fields.Field[].value', 'PaymentMessage.TransactionDetails.message_details.transaction_type', 'PaymentMessage.TransactionDetails.three_d_secure_authentication_exemption.exemption_indicator', 'PaymentMessage.TransactionDetails.three_d_secure_authentication_exemption.exemption_reason', 'PaymentMessage.TransactionDetails.three_d_secure_authentication_exemption.exemption_type', 'PaymentMessage.TransactionDetails.three_d_secure_browser_details.device_category', 'PaymentMessage.TransactionDetails.three_d_secure_passthrough_data.authentication_status', 'PaymentMessage.TransactionDetails.three_d_secure_passthrough_data.eci', 'PaymentMessage.TransactionDetails.three_d_secure_passthrough_data.enrolment_status', 'PaymentMessage.TransactionDetails.three_d_secure_passthrough_data.three_d_secure_version'], 'CrossReferenceTransaction': ['PaymentMessage.MerchantDetails.merchant_identifier', 'PaymentMessage.OverrideCardDetails.expiry_date.month', 'PaymentMessage.OverrideCardDetails.expiry_date.year', 'PaymentMessage.OverrideCardDetails.start_date.month', 'PaymentMessage.OverrideCardDetails.start_date.year', 'PaymentMessage.TransactionDetails.airline_details.airline_invoice_number', 'PaymentMessage.TransactionDetails.airline_details.airline_plan_number', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].arrival_airport_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].carrier_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].departure_airport_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].departure_tax_amount', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].fare_basis_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].flight_number', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].leg_number', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].stop_over_code', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].travel_class', 'PaymentMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].travel_date', 'PaymentMessage.TransactionDetails.airline_details.passenger_details.ticket_number', 'PaymentMessage.TransactionDetails.airline_details.travel_agency_details.agency_code', 'PaymentMessage.TransactionDetails.amount', 'PaymentMessage.TransactionDetails.currency_code', 'PaymentMessage.TransactionDetails.device_capture_id', 'PaymentMessage.TransactionDetails.line_item_details.line_items.LineItem[].amount', 'PaymentMessage.TransactionDetails.line_item_details.line_items.LineItem[].quantity', 'PaymentMessage.TransactionDetails.line_item_details.sales_tax_amount', 'PaymentMessage.TransactionDetails.message_details.alternate_payment_data.method', 'PaymentMessage.TransactionDetails.message_details.alternate_payment_data.method_specific_fields.Field[].name', 'PaymentMessage.TransactionDetails.message_details.alternate_payment_data.method_specific_fields.Field[].value', 'PaymentMessage.TransactionDetails.message_details.cross_reference', 'PaymentMessage.TransactionDetails.message_details.new_transaction', 'PaymentMessage.TransactionDetails.message_details.transaction_type', 'PaymentMessage.TransactionDetails.three_d_secure_authentication_exemption.exemption_indicator', 'PaymentMessage.TransactionDetails.three_d_secure_authentication_exemption.exemption_reason', 'PaymentMessage.TransactionDetails.three_d_secure_authentication_exemption.exemption_type', 'PaymentMessage.TransactionDetails.three_d_secure_browser_details.device_category', 'PaymentMessage.TransactionDetails.three_d_secure_passthrough_data.authentication_status', 'PaymentMessage.TransactionDetails.three_d_secure_passthrough_data.eci', 'PaymentMessage.TransactionDetails.three_d_secure_passthrough_data.enrolment_status', 'PaymentMessage.TransactionDetails.three_d_secure_passthrough_data.three_d_secure_version'], 'ThreeDSecureAuthentication': ['ThreeDSecureMessage.ThreeDSecureInputData.cross_reference'], 'ThreeDSecureEnvironment': ['ThreeDSecureMessage.ThreeDSecureEnvironmentData.cross_reference'], 'GetCardType': [], 'CreatePayByLink': ['CreatePayByLinkMessage.TransactionDetails.airline_details.airline_invoice_number', 'CreatePayByLinkMessage.TransactionDetails.airline_details.airline_plan_number', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].arrival_airport_code', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].carrier_code', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].departure_airport_code', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].departure_tax_amount', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].fare_basis_code', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].flight_number', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].leg_number', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].stop_over_code', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].travel_class', 'CreatePayByLinkMessage.TransactionDetails.airline_details.flight_legs.FlightLeg[].travel_date', 'CreatePayByLinkMessage.TransactionDetails.airline_details.passenger_details.ticket_number', 'CreatePayByLinkMessage.TransactionDetails.airline_details.travel_agency_details.agency_code', 'CreatePayByLinkMessage.TransactionDetails.amount', 'CreatePayByLinkMessage.TransactionDetails.currency_code', 'CreatePayByLinkMessage.TransactionDetails.device_capture_id', 'CreatePayByLinkMessage.TransactionDetails.line_item_details.line_items.LineItem[].amount', 'CreatePayByLinkMessage.TransactionDetails.line_item_details.line_items.LineItem[].quantity', 'CreatePayByLinkMessage.TransactionDetails.line_item_details.sales_tax_amount', 'CreatePayByLinkMessage.TransactionDetails.message_details.alternate_payment_data.method', 'CreatePayByLinkMessage.TransactionDetails.message_details.alternate_payment_data.method_specific_fields.Field[].name', 'CreatePayByLinkMessage.TransactionDetails.message_details.alternate_payment_data.method_specific_fields.Field[].value', 'CreatePayByLinkMessage.TransactionDetails.message_details.payment_link_uid', 'CreatePayByLinkMessage.TransactionDetails.message_details.transaction_type', 'CreatePayByLinkMessage.TransactionDetails.three_d_secure_authentication_exemption.exemption_indicator', 'CreatePayByLinkMessage.TransactionDetails.three_d_secure_authentication_exemption.exemption_reason', 'CreatePayByLinkMessage.TransactionDetails.three_d_secure_authentication_exemption.exemption_type', 'CreatePayByLinkMessage.TransactionDetails.three_d_secure_browser_details.device_category', 'CreatePayByLinkMessage.TransactionDetails.three_d_secure_passthrough_data.authentication_status', 'CreatePayByLinkMessage.TransactionDetails.three_d_secure_passthrough_data.eci', 'CreatePayByLinkMessage.TransactionDetails.three_d_secure_passthrough_data.enrolment_status', 'CreatePayByLinkMessage.TransactionDetails.three_d_secure_passthrough_data.three_d_secure_version']}

ATTR_FIELDS = {method: [_wire_path(path) for path in paths] for method, paths in ATTR_FIELDS.items()}
