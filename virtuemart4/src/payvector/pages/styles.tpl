<style>
#admin-ui-tabs {
	background: #fdfdfd;
}
.paymentGatewayHeadingContainer {
	max-width: 1200px;
}
.paymentGatewayHeading {
	padding-bottom: 0;
	text-align: center;
}
hr {
	clear: both; 
	float: none; 
	width: 100%; 
	height: 2px;
	margin: 1.4em 0;
	border: none; 
	background: #ddd;
	background: -moz-linear-gradient(top, rgb(221,221,211) 50%, rgb(255,255,255) 50%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(50%,rgb(221,221,211)), color-stop(50%,rgb(255,255,255)));
	background: -webkit-linear-gradient(top, rgb(221,221,211) 50%,rgb(255,255,255) 50%);
	background: -o-linear-gradient(top, rgb(221,221,211) 50%,rgb(255,255,255) 50%);
	background: -ms-linear-gradient(top, rgb(221,221,211) 50%,rgb(255,255,255) 50%);
	background: linear-gradient(to bottom, rgb(221,221,211) 50%,rgb(255,255,255) 50%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ddddd3', endColorstr='#ffffff',GradientType=0 );
}
.paymentGatewaySection {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
	font-size: 14px;
	line-height: 20px;
	max-width: 1200px;
	margin-left: 5px;
}
.paymentGatewaySection td, .paymentGatewaySection th {
	padding: 8px;
	border: 1px solid #ccc;
}
.paymentGatewaySection td {
	width: 50%;
}
.paymentGatewaySection tbody tr:nth-child(odd) {
	background-color: #f6f6f6;
}
.paymentGatewaySection tbody tr:last-of-type {
	display: none;
}
.paymentGatewaySection tr:last-of-type td {
	border: 0;
}
#paymentGatewayModal {
	background: #f2dede;
	border: 1px solid #ebccd1;
	border-radius: 4px;
	color: #a94442;
	display: none;
	padding: 2px 6px;
	position: fixed;
	left: 50%;
	top:50%;
	width: 400px;
	height: 150px;
	margin: -76px 0 0 -203px;
	z-index: 99999999;
}
#paymentGatewayModal p {
	margin-left: 0;
	margin-right: 0;
}
#modalBackground 
{
	background-color: #444;
	display: none;
	opacity: 0.6;
	-moz-opacity: 0.6;
	filter: alpha(opacity=60);
	position: fixed;
	height: 100%;
	width: 100%;
	left: 0;
	top: 0;
	z-index: 99999998;
}

</style>

<div id="modalBackground"></div>
<div id="paymentGatewayModal">
	<h3>Warning</h3>
	<p>Overriding the AVS settings is not recommended under normal use of the plugin, it is recommeded that you change this in the MMS settings for continued use.</p>
	<button type="button" id="avsOverrideOk" value="OK">OK</button>
	<button type="button" id="avsOverrideCancel" value="Cancel">Cancel</button>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script>
	var jQuery_1_10_2 = jQuery.noConflict(true);

	jQuery_1_10_2(document).ready(function() {
		var override1 = jQuery_1_10_2("#paramsavs_override_char1").parent().parent();
		var override2 = jQuery_1_10_2("#paramsavs_override_char2").parent().parent();
		var override3 = jQuery_1_10_2("#paramsavs_override_char3").parent().parent();
		var override4 = jQuery_1_10_2("#paramsavs_override_char4").parent().parent();
		var overrideTrue = jQuery_1_10_2("#paramsoverride_avstrue");
		var overrideFalse = jQuery_1_10_2("#paramsoverride_avsfalse");
		var overrideButtonOK = jQuery_1_10_2("#avsOverrideOk");
		var overrideButtonCancel = jQuery_1_10_2("#avsOverrideCancel");
		var modalBackground = jQuery_1_10_2("#modalBackground");
		var modal = jQuery_1_10_2("#paymentGatewayModal");

		function paymentGatewayToggleOverrides(hide) {
			if(hide === true)
			{
				override1.hide();
				override2.hide();
				override3.hide();
				override4.hide();
			}
			else
			{
				override1.show();
				override2.show();
				override3.show();
				override4.show();
			}
		}

		if(overrideFalse.is(":checked"))
		{
			paymentGatewayToggleOverrides(true);
		}
		else
		{
			paymentGatewayToggleOverrides(false);
		}

		overrideTrue.click(function() {
			if(overrideTrue.is(":checked"))
			{
				modalBackground.show();
				modal.show();
				paymentGatewayToggleOverrides(false);
			}
		});
		overrideFalse.click(function() {
			if(overrideFalse.is(":checked"))
			{
				paymentGatewayToggleOverrides(true);
			}
		});

		overrideButtonOK.click(function() {
			modalBackground.hide();
			modal.hide();
		});
		overrideButtonCancel.click(function() {
			modalBackground.hide();
			modal.hide();
			paymentGatewayToggleOverrides(true);
			overrideFalse.click();
		});

		var captureMethod = jQuery_1_10_2("#paramscapture_method");

		var siteSecureDomain = jQuery_1_10_2("#paramssite_secure_domain");
		var siteSecurePort = jQuery_1_10_2("#paramssite_secure_port");
		var paymentProcessorDomain = jQuery_1_10_2("#paramspayment_processor_domain");
		var paymentProcessorPort = jQuery_1_10_2("#paramspayment_processor_port");

		var hashMethod = jQuery_1_10_2("#paramshash_method").parent().parent();
		var preSharedKey = jQuery_1_10_2("#paramspre_shared_key").parent().parent();
		var resultDeliveryMethod = jQuery_1_10_2("#paramsresult_delivery_method").parent().parent();
		
		if(captureMethod.val() === "Direct")
		{
			hashMethod.hide();
			preSharedKey.hide();
			resultDeliveryMethod.hide();
		}

		jQuery_1_10_2("#paramscapture_method_chzn").click(function() {
			if(captureMethod.val() === "Direct")
			{
				hashMethod.hide();
				preSharedKey.hide();
				resultDeliveryMethod.hide();
				siteSecureDomain.show();
				siteSecurePort.show();
				paymentProcessorDomain.show();
				paymentProcessorPort.show();
			}
			else
			{
				hashMethod.show();
				preSharedKey.show();
				resultDeliveryMethod.show();
				siteSecureDomain.hide();
				siteSecurePort.hide();
				paymentProcessorDomain.hide();
				paymentProcessorPort.hide();
			}
		});

    	var testMerhchantID = jQuery_1_10_2("#paramstest_mid").parent().parent();
		var testMerchantPass = jQuery_1_10_2("#paramstest_pass").parent().parent();
		var liveMerchantID = jQuery_1_10_2("#paramslive_mid").parent().parent();
		var liveMerchantPass = jQuery_1_10_2("#paramslive_pass").parent().parent();

		if(jQuery_1_10_2("#paramstestmode1").is(":checked"))
		{
			liveMerchantID.hide();
			liveMerchantPass.hide();
		}
		else
		{
			testMerhchantID.hide();
			testMerchantPass.hide();
		}

		jQuery_1_10_2("#paramstestmode0, #paramstestmode1").click(function() {
			if(jQuery_1_10_2(this).is(":checked"))
			{
				liveMerchantID.toggle();
				liveMerchantPass.toggle();
				testMerhchantID.toggle();
				testMerchantPass.toggle();
			}
		});
	});

	
</script>