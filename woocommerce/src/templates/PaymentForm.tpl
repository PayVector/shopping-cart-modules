<?php
if(!empty($cardLastFour) && $this->get_option('enable_saved_card') === "yes")
	{
	?>
		<input id="storedCard" type="radio" name="payment_type" value="stored_card" style="cursor: pointer;" checked>
		<label for="storedCard" style="cursor: pointer;">
		<?php
			if(!empty($cardLastFour))
			{
				echo sprintf( __( 'Use saved %s card: xxxx-%s', 'woocommerce' ), $cardType, $cardLastFour );
			}
			else
			{
				echo sprintf( __( 'Use saved %s card', 'woocommerce' ), $cardType );
			}
		?>
		</label>
	<table id="payvector-table-stored_card"  class="woocommerce-checkout-payment">
	<tr class="pay_vector_cc_details form-row form-row-wide pay_vector_cvv_sc">
		<td><?php echo __( 'CVV <strong>*</strong>', 'woocommerce' ) ?></td>
		<td><input id="cvv" type='text' size='4' value='' maxlength='4' name='card_code_sc' class='input-text' title="" autocomplete="off" /></td>
		<br>
	</tr>
	</table>

	<?php
	}
	if($this->captureMethod === IntegrationMethod::HostedPaymentForm && !empty($cardLastFour) && $this->get_option('enable_saved_card') === "yes")
	{
	?>
		<input id="newCard" type="radio" name="payment_type" value="new_card" style="cursor: pointer;">
		<label for="newCard" style="cursor: pointer;">
		<?php echo __( 'Or enter card details on purchase', 'woocommerce' ) ?>
		</label>
	<?php
	}
	else if($this->captureMethod === IntegrationMethod::HostedPaymentForm && $this->get_option('enable_saved_card') === "yes")
	{
	?>
		<input id="newCard" type="radio" name="payment_type" value="new_card" style="cursor: pointer;">
		<label for="newCard" style="cursor: pointer;">
		<?php echo $this->description ?>
		</label>
	<?php
	}
	else if($this->captureMethod === IntegrationMethod::HostedPaymentForm)
	{
	?>
		<input id="newCard" type="radio" name="payment_type" value="new_card" style="cursor: pointer;" checked>
		<label for="newCard" style="cursor: pointer;">
		<?php echo $this->description ?>
		</label>
	<?php
	}
	else if((!empty($cardLastFour) && $this->get_option('enable_saved_card') === "yes"))
	{
	?>
		<input id="newCard" type="radio" name="payment_type" value="new_card" style="cursor: pointer;">
		<label for="newCard" style="cursor: pointer;">
		<?php echo __( 'Or Enter new card details', 'woocommerce' ) ?>
		</label>
	<?php
	}
	else
	{
	?>
		<?php echo __( 'Enter card details', 'woocommerce' ) ?>
		<input id="newCard" type="hidden" name="payment_type" value="new_card">
	<?php
	}

	if( $this->captureMethod === IntegrationMethod::DirectAPI || ($this->captureMethod === IntegrationMethod::HostedPaymentForm && $this->get_option('enable_saved_card') === "yes") )
	{
		
?>

<!-- 3DSv2 Hidden Fields -->
        <input type="hidden" name="browserjavaenabled" id="browserJavaEnabled" value="">
        <input type="hidden" name="browserlanguage" id="browserLanguage" value="">
        <input type="hidden" name="browsercolordepth" id="browserColorDepth" value="">
        <input type="hidden" name="browserscreenheight" id="browserScreenHeight" value="">
        <input type="hidden" name="browserscreenwidth" id="browserScreenWidth" value="">
        <input type="hidden" name="browsertz" id="browserTZ" value="">
		<input type="hidden" name="browserUserAgent" id="browserUserAgent" value="">
<table id="payvector-table woocommerce-checkout-payment">
	<input id="captureMethod" type="hidden" value="<?php echo $this->captureMethod ?>" />
	<?php
		if( $this->captureMethod === IntegrationMethod::DirectAPI) {
	?>
	<tr class="pay_vector_cc_details form-row form-row-wide">
		<td><?php echo __( 'Credit Card Number <strong>*</strong>', 'woocommerce' ) ?></td>
		<td>
			<input type='text' value='' name='card_number' class="input-text" required />
		</td>
	</tr>
	
	<tr class="pay_vector_cc_details form-row form-row-wide">
		<td><?php echo __( 'Credit Card Expiry Date <strong>*</strong>', 'woocommerce' ) ?></td>
		<td>
         <div style="display: flex; gap: 20px;">
			<select class='wpsc_ccBox' name='expirymonth' style="width: 7em;">
				<option value=''></option>
				<option value='01'>01</option>
				<option value='02'>02</option>
				<option value='03'>03</option>
				<option value='04'>04</option>
				<option value='05'>05</option>
				<option value='06'>06</option>
				<option value='07'>07</option>
				<option value='08'>08</option>
				<option value='09'>09</option>
				<option value='10'>10</option>
				<option value='11'>11</option>
				<option value='12'>12</option>
			</select> 
			<select class='wpsc_ccBox' name='expiryyear' style="width: 7em;">
				<?php
					foreach($expiryYear as $year)
					{
						echo "<option value='$year'>$year</option>";
					}
				?>
			</select>
            </div>
		</td>
	</tr>
	<tr class="pay_vector_cc_details form-row form-row-wide">
		<td><?php echo __( 'Issue Number', 'woocommerce' ) ?></td>
		<td><input id="issue_number" type='text' size='4' value='' maxlength='4' name='issue_number' class='pay_vector_issue_number input-text' title="" style="width: 7em;"/></td>
	</tr>
	<tr class="pay_vector_cc_details form-row form-row-wide">
		<td><?php echo __( 'CVV <strong>*</strong>', 'woocommerce' ) ?></td>
		<td><input id="cvv" type='text' size='4' value='' maxlength='4' name='card_code' class='pay_vector_cvv input-text' title="" style="width: 7em;"/></td>
	</tr>
	<?php
		}
	?>
</table>
<?php
	}
if (!$this->hash_block) {	
?>	
	<script>
    jQuery(document).on("ready ajaxComplete", function() {
		var paymentType = jQuery("input[name='payment_type']");
		var storedCard = jQuery("#storedCard");
		var newCard = jQuery("#newCard");
		var cardDetailsInput = jQuery(".pay_vector_cc_details");
		var captureMethod = jQuery('#captureMethod');
		var cvvContainer = jQuery(".pay_vector_cvv_sc");
		var cvv = jQuery("#cvv");
		var requiredField = jQuery("#payvector-required");
		var payvectorTable = jQuery('#payvector-table');
		
		if(captureMethod.val() === "<?php echo IntegrationMethod::HostedPaymentForm ?>")
		{
			cardDetailsInput.hide();
			cvvContainer.show();
			
			if(paymentType.val() === "new_card")
			{
				cvvContainer.hide();
				requiredField.hide();
				cvv.prop('disabled', true);
			}
			
			paymentType.click(function() {
				if(jQuery(this).val() === "new_card")
				{
					payvectorTable.hide();
					cvvContainer.hide();
					requiredField.hide();
					cvv.prop('disabled', true);
				}
				else
				{
					payvectorTable.show();
					cvv.prop('disabled', false);
					cvvContainer.show();
					requiredField.show();
				}
			});
		}
		else
		{
			if(storedCard.is(":checked"))
			{
				cardDetailsInput.hide();
				cvvContainer.show();
			}
			
			paymentType.change(function() {
				if(jQuery(this).val() === "new_card")
				{
					cardDetailsInput.show();
					cvvContainer.hide();
				}
				else
				{
					cardDetailsInput.hide();
					cvvContainer.show();
				}
			});
		}

 // Collect 3DSv2 Parameters
    function get3DSv2Params() {
        document.getElementById("browserJavaEnabled").value = navigator.javaEnabled();
        document.getElementById("browserLanguage").value = navigator.language || navigator.userLanguage;
        document.getElementById("browserColorDepth").value = screen.colorDepth;
        document.getElementById("browserScreenHeight").value = screen.height;
        document.getElementById("browserScreenWidth").value = screen.width;
        document.getElementById("browserTZ").value = new Date().getTimezoneOffset();
        document.getElementById("browserUserAgent").value = navigator.userAgent;
    }

    get3DSv2Params();

	});
</script>

<?php
}
?>