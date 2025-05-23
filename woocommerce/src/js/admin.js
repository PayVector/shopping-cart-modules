jQuery(document).ready(function () {
	var $testMode = jQuery("#woocommerce_payvector_test_mode");
	var $liveMidRow = jQuery("#woocommerce_payvector_live_mid").closest('tr');
	var $livePasswordRow = jQuery("#woocommerce_payvector_live_password").closest('tr');
	var $testMidRow = jQuery("#woocommerce_payvector_test_mid").closest('tr');
	var $testPasswordRow = jQuery("#woocommerce_payvector_test_password").closest('tr');

	var $captureMethod = jQuery('#woocommerce_payvector_capture_method');
	var $preSharedKeyRow = jQuery('#woocommerce_payvector_pre_shared_key').closest('tr');
	var $hashMethodRow = jQuery('#woocommerce_payvector_hash_method').closest('tr');
	var $resultDeliveryMethodRow = jQuery('#woocommerce_payvector_result_delivery_method').closest('tr');

	var $savedCardEnabled = jQuery('#woocommerce_payvector_enable_saved_card');
	var $3dsCrossReferenceRow = jQuery('#woocommerce_payvector_enable_3ds_cross_reference').closest('tr');

	//	var $recurringTransactions = jQuery('#woocommerce_payvector_recurring_transactions');
	//	var $recurringTransactionsRow = $recurringTransactions.closest('tr');
	//	var recurringTransactionsLabel = $recurringTransactionsRow.find('label').eq(1).text();
	//	var $CAMidRow = jQuery('#woocommerce_payvector_ca_mid').closest('tr');
	//	var $CAPasswordRow = jQuery('#woocommerce_payvector_ca_password').closest('tr');

	if ($savedCardEnabled.is(":checked")) {
		$3dsCrossReferenceRow.show();
	}
	else {
		$3dsCrossReferenceRow.hide();
	}

	$savedCardEnabled.on("keyup change click", function () {
		if ($savedCardEnabled.is(":checked")) {
			$3dsCrossReferenceRow.show();
		}
		else {
			$3dsCrossReferenceRow.hide();
		}
	});

	if ($testMode.is(":checked")) {
		$liveMidRow.hide();
		$livePasswordRow.hide();
	} else {
		$testMidRow.hide();
		$testPasswordRow.hide();
	}

	//	if(recurringTransactionsLabel !== "Available" || $recurringTransactions.val() === "no") {
	//		$CAMidRow.hide();
	//		$CAPasswordRow.hide();
	//	}
	//
	//	$recurringTransactions.on("click change", function() {
	//		if(jQuery(this).is(":checked")) {
	//			$CAMidRow.show();
	//			$CAPasswordRow.show();
	//		} else {
	//			$CAMidRow.hide();
	//			$CAPasswordRow.hide();
	//		}
	//	});

	$testMode.on("keyup change click", function () {
		if ($testMode.is(":checked")) {
			$liveMidRow.hide();
			$livePasswordRow.hide();
			$testMidRow.show();
			$testPasswordRow.show();
		} else {
			$testMidRow.hide();
			$testPasswordRow.hide();
			$liveMidRow.show();
			$livePasswordRow.show();
		}
	});

	if ($captureMethod.find(':selected').val() === "Direct API") {
		$preSharedKeyRow.hide();
		$hashMethodRow.hide();
		$resultDeliveryMethodRow.hide();
	}

	$captureMethod.on("keyup, change, click", function () {
		if ($captureMethod.find(':selected').val() === "Direct API") {
			$preSharedKeyRow.hide();
			$hashMethodRow.hide();
			$resultDeliveryMethodRow.hide();
		} else {
			$preSharedKeyRow.show();
			$hashMethodRow.show();
			$resultDeliveryMethodRow.show();
		}
	});
});
