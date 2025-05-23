<form action="https://mms.<?php echo $PaymentProcessorDomain ?>/Pages/PublicPages/PaymentForm.aspx" method="post" id="HPFForm">
	<input type="hidden" name="HashDigest" value="<?php echo $viewArray['HashDigest'] ?>" />
	<input type="hidden" name="MerchantID" value="<?php echo $viewArray['MerchantID'] ?>" />
	<input type="hidden" name="Amount" value="<?php echo $viewArray['Amount'] ?>" />
	<input type="hidden" name="CurrencyCode" value="<?php echo $viewArray['CurrencyCode'] ?>" />
	<input type="hidden" name="OrderID" value="<?php echo $viewArray['OrderID'] ?>" />
	<input type="hidden" name="TransactionType" value="<?php echo $viewArray['TransactionType'] ?>" />
	<input type="hidden" name="TransactionDateTime" value="<?php echo $viewArray['TransactionDateTime'] ?>" />
	<input type="hidden" name="CallbackURL" value="<?php echo htmlspecialchars($viewArray['CallbackURL'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="OrderDescription" value="<?php echo $viewArray['OrderDescription'] ?>" />
	<input type="hidden" name="CustomerName" value="<?php echo htmlspecialchars($viewArray['CustomerName'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="Address1" value="<?php echo htmlspecialchars($viewArray['Address1'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="Address2" value="<?php echo htmlspecialchars($viewArray['Address2'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="Address3" value="<?php echo htmlspecialchars($viewArray['Address3'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="Address4" value="<?php echo htmlspecialchars($viewArray['Address4'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="City" value="<?php echo htmlspecialchars($viewArray['City'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="State" value="<?php echo htmlspecialchars($viewArray['State'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="PostCode" value="<?php echo htmlspecialchars($viewArray['Postcode'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="CountryCode" value="<?php echo $viewArray['CountryCode'] ?>" />
	<input type="hidden" name="CV2Mandatory" value="true" />
	<input type="hidden" name="Address1Mandatory" value="false" />
	<input type="hidden" name="CityMandatory" value="false" />
	<input type="hidden" name="PostCodeMandatory" value="false" />
	<input type="hidden" name="StateMandatory" value="false" />
	<input type="hidden" name="CountryMandatory" value="false" />
	<input type="hidden" name="ResultDeliveryMethod" value="<?php echo $viewArray['ResultDeliveryMethod'] ?>" />
	<input type="hidden" name="ServerResultURL" value="<?php echo htmlspecialchars($viewArray['ServerResultURL'] ?? '', ENT_QUOTES) ?>" />
	<input type="hidden" name="PaymentFormDisplaysResult" value="<?php echo $viewArray['PaymentFormDisplaysResult'] ?>" />
	<input type="hidden" name="EmailAddress" value="<?php echo $viewArray['EmailAddress'] ?>" />
	<input type="hidden" name="PhoneNumber" value="<?php echo $viewArray['PhoneNumber'] ?>" />
	<input type="hidden" name="EchoAVSCheckResult" value="true" />
	<input type="hidden" name="EchoCV2CheckResult" value="true" />
	<input type="hidden" name="EchoThreeDSecureAuthenticationCheckResult" value="true" />
	<input type="hidden" name="EchoCardType" value="true" />
	<input type="hidden" name="ServerResultURLCookieVariables" value="" />
	<input type="hidden" name="ServerResultURLFormVariables" value="" />
	<input type="hidden" name="ServerResultURLQueryStringVariables" value="" />

	<div id="HPFRedirect" style="width: 400px; margin: 0 auto;">
		<h3 style="text-align: center;">
			<?php echo "Please wait while you are redirected" ?>
		</h3>
		<center>
			<img src="<?php echo JURI::base(); ?>/plugins/vmpayment/payvector/assets/AJAXSpinner.gif"/>
		</center>
	</div>
	<noscript>
		<p>If you are not automatically redirected, click here:</p>
		<input type="submit" value="Continue to Payment" />
	</noscript>
</form>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var hpfRedirect = document.getElementById("HPFRedirect");
        var hpfForm = document.getElementById("HPFForm");
        if (hpfRedirect) {
            hpfRedirect.style.display = "block";
        }

        setTimeout(function () {
            if (hpfForm) {
                hpfForm.submit();
            }
        }, 500);
    });
</script>