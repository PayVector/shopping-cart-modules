from __future__ import annotations
import urllib.request
import urllib.error
import xml.etree.ElementTree as ET
from typing import Any
from .models import *
from ._wire import build_envelope, ATTR_FIELDS, lname, child, children, text, as_int, as_bool, METHOD_URI

class GatewayTransaction:
    method: str = ""
    message_name: str = ""
    result_name: str = ""
    output_name: str = ""
    def __init__(self):
        self.merchant_authentication=MerchantAuthentication()
        self.request_gateway_entry_points=RequestGatewayEntryPointList()
        self.retry_attempts=1
        self.timeout=None
        self.last_exception=None
        self.last_request=None
        self.last_response=None
        self.entry_point_used=None
    def _objects(self): return []
    def build_request(self):
        return build_envelope(self.method,self.message_name,self._objects(),self.merchant_authentication,set(ATTR_FIELDS.get(self.__class__.__name__,())))
    def _send(self,url,xml):
        headers={"Content-Type":"text/xml; charset=utf-8","SOAPAction":f'"{METHOD_URI}{self.method}"',"User-Agent":"ThePaymentGateway SOAP Library Python"}
        req=urllib.request.Request(url,data=xml.encode('utf-8'),headers=headers,method='POST')
        timeout=(self.timeout/1000.0) if self.timeout else None  # .NET timeout is milliseconds
        with urllib.request.urlopen(req,timeout=timeout) as resp:
            return resp.read().decode(resp.headers.get_content_charset() or 'utf-8')
    def process_transaction(self):
        self.last_exception=self.last_response=self.entry_point_used=None
        self.last_request=self.build_request()
        eps=sorted(list(self.request_gateway_entry_points),key=lambda x:x.metric if x.metric is not None else 0)
        try:
            for _ in range(max(0,int(self.retry_attempts or 0))):
                for ep in eps:
                    if ep.metric is not None and ep.metric < 0: continue
                    for _ in range(max(0,int(ep.retry_attempts or 0))):
                        try:
                            response=self._send(ep.entry_point_url,self.last_request)
                            self.last_response=response
                            root=ET.fromstring(response)
                            result=self._find(root,self.result_name)
                            status=as_int(text(result,'status_code'))
                            if status is not None and status != 50:
                                self.entry_point_used=ep.entry_point_url
                                self._parse_success(root,result)
                                return True
                        except Exception as exc:
                            self.last_exception=exc
            return False
        except Exception as exc:
            self.last_exception=exc; return False
    @staticmethod
    def _find(root,name):
        for n in root.iter():
            if lname(n.tag)==name:return n
        return None
    def _parse_gateway_output(self,result):
        errors=[]
        em=child(result,'error_messages')
        for md in children(em,'MessageDetail'):
            d=text(md,'Detail')
            if d is not None: errors.append(d)
        return GatewayOutput(status_code=as_int(text(result,'status_code'),0),message=text(result,'message'),error_messages=errors)
    def _parse_payment_result(self,result, cls):
        go=self._parse_gateway_output(result)
        obj=cls()
        for f in ('status_code','message','error_messages'): setattr(obj,f,getattr(go,f))
        obj.authorisation_attempted=as_bool(text(result,'authorisation_attempted'))
        pr=child(result,'previous_transaction_result')
        if pr is not None:
            obj.previous_transaction_result=PreviousTransactionResult(status_code=as_int(text(pr,'status_code')),message=text(pr,'message'))
        return obj
    def _parse_entry_points(self,node):
        out=GatewayEntryPointList(); gep=child(node,'gateway_entry_points') if node is not None else None
        for e in children(gep,'GatewayEntryPoint'):
            out.add(GatewayEntryPoint(text(e,'entry_point_url'),as_int(text(e,'metric'),-1)))
        return out
    def _parse_card_type(self,node):
        if node is None:return None
        issuer_node=child(node,'issuer')
        issuer=Issuer(name=(issuer_node.text if issuer_node is not None and issuer_node.text else text(issuer_node,'name')),iso_code=as_int(text(issuer_node,'iso_code'))) if issuer_node is not None else None
        # Some gateway XML represents issuer name as element text plus ISOCode child.
        if issuer_node is not None and issuer.name is None and issuer_node.text: issuer.name=issuer_node.text.strip()
        return CardTypeData(card_type=text(node,'card_type'),card_class=text(node,'card_class'),issuer=issuer,luhn_check_required=as_bool(text(node,'luhn_check_required')),issue_number_status=text(node,'issue_number_status'),start_date_status=text(node,'start_date_status'))
    def _parse_transaction_output(self,node):
        if node is None:return None
        o=TransactionOutputData()
        for f in ('cross_reference','external_client_reference','external_cross_reference','external_transaction_uid','external_response_code','external_message','auth_code','address_numeric_check_result','post_code_check_result','three_d_secure_authentication_check_result','cv2_check_result','fraud_protection_check_result','scheme_transaction_id'):
            val = node.attrib.get(WIRE_NAMES.get(f, f)) or text(node,f)
            setattr(o,f,val)
        fpr=child(node,'fraud_protection_check_result')
        o.fraud_protection_reason_code=text(fpr,'fraud_protection_reason_code')
        o.amount_received=as_int(text(node,'amount_received'))
        o.card_type_data=self._parse_card_type(child(node,'card_type_data'))
        t=child(node,'three_d_secure_output_data')
        if t is not None:
            o.three_d_secure_output_data=ThreeDSecureOutputData(pa_req=text(t,'pa_req'),acs_url=text(t,'acs_url'),three_d_secure_version=text(t,'three_d_secure_version','1.1.0') or '1.1.0',creq=text(t,'creq'),method_data=text(t,'method_data'),method_url=text(t,'method_url'))
        cv=child(node,'custom_variables')
        if cv is not None:
            c=CustomVariables()
            for v in children(cv,'Variable'): c.add(text(v,'name'),text(v,'value',''))
            o.custom_variables=c
        o.gateway_entry_points=self._parse_entry_points(node)
        return o

class GetGatewayEntryPoints(GatewayTransaction):
    method='GetGatewayEntryPoints'; message_name='GetGatewayEntryPointsMessage'; result_name='GetGatewayEntryPointsResult'; output_name='GetGatewayEntryPointsOutputData'
    def __init__(self): super().__init__(); self.get_gateway_entry_points_result=None; self.get_gateway_entry_points_output_data=None
    def _parse_success(self,root,result):
        go=self._parse_gateway_output(result); self.get_gateway_entry_points_result=GetGatewayEntryPointsResult(**{f:getattr(go,f) for f in ('status_code','message','error_messages')})
        out=self._find(root,self.output_name); obj=GetGatewayEntryPointsOutputData(); obj.gateway_entry_points=self._parse_entry_points(out); self.get_gateway_entry_points_output_data=obj

class CardDetailsTransaction(GatewayTransaction):
    method='CardDetailsTransaction'; message_name='PaymentMessage'; result_name='CardDetailsTransactionResult'; output_name='TransactionOutputData'
    def __init__(self):
        super().__init__(); self.transaction_details=TransactionDetails(); self.card_details=CardDetails(); self.customer_details=CustomerDetails(); self.merchant_details=MerchantDetails(); self.card_details_transaction_result=None; self.transaction_output_data=None
    def _objects(self): return [('TransactionDetails',self.transaction_details),('CardDetails',self.card_details),('CustomerDetails',self.customer_details),('MerchantDetails',self.merchant_details)]
    def _parse_success(self,root,result): self.card_details_transaction_result=self._parse_payment_result(result,CardDetailsTransactionResult); self.transaction_output_data=self._parse_transaction_output(self._find(root,self.output_name))

class CrossReferenceTransaction(GatewayTransaction):
    method='CrossReferenceTransaction'; message_name='PaymentMessage'; result_name='CrossReferenceTransactionResult'; output_name='TransactionOutputData'
    def __init__(self):
        super().__init__(); self.transaction_details=TransactionDetails(); self.override_card_details=OverrideCardDetails(); self.customer_details=CustomerDetails(); self.merchant_details=MerchantDetails(); self.cross_reference_transaction_result=None; self.transaction_output_data=None
    def _objects(self): return [('TransactionDetails',self.transaction_details),('OverrideCardDetails',self.override_card_details),('CustomerDetails',self.customer_details),('MerchantDetails',self.merchant_details)]
    def _parse_success(self,root,result): self.cross_reference_transaction_result=self._parse_payment_result(result,CrossReferenceTransactionResult); self.transaction_output_data=self._parse_transaction_output(self._find(root,self.output_name))

class ThreeDSecureAuthentication(GatewayTransaction):
    method='ThreeDSecureAuthentication'; message_name='ThreeDSecureMessage'; result_name='ThreeDSecureAuthenticationResult'; output_name='TransactionOutputData'
    def __init__(self): super().__init__(); self.three_d_secure_input_data=ThreeDSecureInputData(); self.three_d_secure_authentication_result=None; self.transaction_output_data=None
    def _objects(self): return [('ThreeDSecureInputData',self.three_d_secure_input_data)]
    def _parse_success(self,root,result): self.three_d_secure_authentication_result=self._parse_payment_result(result,ThreeDSecureAuthenticationResult); self.transaction_output_data=self._parse_transaction_output(self._find(root,self.output_name))

class ThreeDSecureEnvironment(GatewayTransaction):
    method='ThreeDSecureEnvironment'; message_name='ThreeDSecureMessage'; result_name='ThreeDSecureEnvironmentResult'; output_name='TransactionOutputData'
    def __init__(self): super().__init__(); self.three_d_secure_environment_data=ThreeDSecureEnvironmentData(); self.three_d_secure_environment_result=None; self.transaction_output_data=None
    def _objects(self): return [('ThreeDSecureEnvironmentData',self.three_d_secure_environment_data)]
    def _parse_success(self,root,result): self.three_d_secure_environment_result=self._parse_payment_result(result,ThreeDSecureEnvironmentResult); self.transaction_output_data=self._parse_transaction_output(self._find(root,self.output_name))

class GetCardType(GatewayTransaction):
    method='GetCardType'; message_name='GetCardTypeMessage'; result_name='GetCardTypeResult'; output_name='GetCardTypeOutputData'
    def __init__(self): super().__init__(); self.card_number=None; self.get_card_type_result=None; self.get_card_type_output_data=None
    def _objects(self):
        tmp=Model(); tmp._fields=('card_number',); tmp.card_number=self.card_number; return [('__flatten__',tmp)]
    def build_request(self):
        # CardNumber is directly beneath GetCardTypeMessage.
        from ._wire import SOAP_ENV
        import xml.etree.ElementTree as ET
        env=ET.Element(f'{{{SOAP_ENV}}}Envelope'); body=ET.SubElement(env,f'{{{SOAP_ENV}}}Body'); m=ET.SubElement(body,self.method);m.set('xmlns',METHOD_URI);msg=ET.SubElement(m,self.message_name)
        if self.merchant_authentication.merchant_id or self.merchant_authentication.password:
            ma=ET.SubElement(msg,'MerchantAuthentication')
            if self.merchant_authentication.merchant_id: ma.set('MerchantID',str(self.merchant_authentication.merchant_id))
            if self.merchant_authentication.password: ma.set('Password',str(self.merchant_authentication.password))
        if self.card_number: ET.SubElement(msg,'CardNumber').text=str(self.card_number)
        return ET.tostring(env,encoding='utf-8',xml_declaration=True).decode()
    def _parse_success(self,root,result):
        go=self._parse_gateway_output(result); self.get_card_type_result=GetCardTypeResult(**{f:getattr(go,f) for f in ('status_code','message','error_messages')}); out=self._find(root,self.output_name); o=GetCardTypeOutputData(); o.card_type_data=self._parse_card_type(child(out,'card_type_data')); o.gateway_entry_points=self._parse_entry_points(out); self.get_card_type_output_data=o

class CreatePayByLink(GatewayTransaction):
    method='CreatePayByLink'; message_name='CreatePayByLinkMessage'; result_name='CreatePayByLinkResult'; output_name='CreatePayByLinkOutputData'
    def __init__(self): super().__init__(); self.transaction_details=TransactionDetails(); self.customer_details=CustomerDetails(); self.create_pay_by_link_result=None; self.create_pay_by_link_output_data=None
    def _objects(self): return [('TransactionDetails',self.transaction_details),('CustomerDetails',self.customer_details)]
    def _parse_success(self,root,result):
        go=self._parse_gateway_output(result); self.create_pay_by_link_result=CreatePayByLinkResult(**{f:getattr(go,f) for f in ('status_code','message','error_messages')}); out=self._find(root,self.output_name); o=CreatePayByLinkOutputData(); o.payment_link_uid=text(out,'payment_link_uid'); cv=child(out,'custom_variables');
        if cv is not None:
            o.custom_variables=CustomVariables(); [o.custom_variables.add(text(v,'name'),text(v,'value','')) for v in children(cv,'Variable')]
        o.gateway_entry_points=self._parse_entry_points(out); self.create_pay_by_link_output_data=o
