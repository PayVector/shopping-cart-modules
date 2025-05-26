<form action="{$hosted_payment_form_url}" method="get" id="HPFForm">
	<input type="hidden" name="HashDigest" value="{$view_array.HashDigest}" />
	<input type="hidden" name="MerchantID" value="{$view_array.MerchantID}" />
	<input type="hidden" name="Amount" value="{$view_array.Amount}" />
	<input type="hidden" name="CurrencyCode" value="{$view_array.CurrencyCode}" />
	<input type="hidden" name="OrderID" value="{$view_array.OrderID}" />
	<input type="hidden" name="TransactionType" value="{$view_array.TransactionType}" />
	<input type="hidden" name="TransactionDateTime" value="{$view_array.TransactionDateTime}" />
	<input type="hidden" name="CallbackURL" value="{$view_array.CallbackURL|escape}" />
	<input type="hidden" name="OrderDescription" value="{$view_array.OrderDescription}" />
	<input type="hidden" name="CustomerName" value="{$view_array.CustomerName|escape}" />
	<input type="hidden" name="Address1" value="{$view_array.Address1|escape}" />
	<input type="hidden" name="Address2" value="{$view_array.Address2|escape}" />
	<input type="hidden" name="Address3" value="{$view_array.Address3|escape}" />
	<input type="hidden" name="Address4" value="{$view_array.Address4|escape}" />
	<input type="hidden" name="City" value="{$view_array.City|escape}" />
	<input type="hidden" name="State" value="{$view_array.State|escape}" />
	<input type="hidden" name="PostCode" value="{$view_array.Postcode|escape}" />
	<input type="hidden" name="CountryCode" value="{$view_array.CountryCode}" />
	<input type="hidden" name="CV2Mandatory" value="true" />
	<input type="hidden" name="Address1Mandatory" value="false" />
	<input type="hidden" name="CityMandatory" value="false" />
	<input type="hidden" name="PostCodeMandatory" value="false" />
	<input type="hidden" name="StateMandatory" value="false" />
	<input type="hidden" name="CountryMandatory" value="false" />
	<input type="hidden" name="ResultDeliveryMethod" value="{$view_array.ResultDeliveryMethod}" />
	<input type="hidden" name="ServerResultURL" value="{$view_array.ServerResultURL|escape}" />
	<input type="hidden" name="PaymentFormDisplaysResult" value="{$view_array.PaymentFormDisplaysResult}" />
	<input type="hidden" name="EmailAddress" value="{$view_array.EmailAddress}" />
	<input type="hidden" name="PhoneNumber" value="{$view_array.PhoneNumber}" />
	<input type="hidden" name="EchoAVSCheckResult" value="true" />
	<input type="hidden" name="EchoCV2CheckResult" value="true" />
	<input type="hidden" name="EchoThreeDSecureAuthenticationCheckResult" value="true" />
	<input type="hidden" name="EchoCardType" value="true" />
	<input type="hidden" name="ServerResultURLCookieVariables" value="" />
	<input type="hidden" name="ServerResultURLFormVariables" value="" />
	<input type="hidden" name="ServerResultURLQueryStringVariables" value="" />

	<div id="HPFRedirect">
		<h3>
			{l s='Please wait while you are redirected to our secure payment page' mod='payvector'}
		</h3>		
	</div>
	<noscript>
		<p>If you are not automatically redirected, click here:</p>
		<input type="submit" value="Continue to Payment" />
	</noscript>
</form>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>
	var jQuery_1_11_2 = jQuery.noConflict(true);

	jQuery_1_11_2( document ).ready(function() {
		jQuery_1_11_2("#HPFRedirect").show();

		setTimeout(function(){
			jQuery_1_11_2("#HPFForm").submit();
		},500);
	});
</script>