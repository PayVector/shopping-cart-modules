<?php
	if($unsupportedCurrency)
	{
?>
<tr>
	<td colspan="2" style="background-color: #f2dede">
		<p style="color: #a94442">
			<?php echo __( "Warning - the WP Ecommerce store is set to a currency not supported by this plugin", 'wpsc') ?>
			<input type="hidden" name="PayVector[UnsupportedCurrency]" value="true">
		</p>
	</td>
</tr>
<?php
	}
?>
<tr>
	<td>
		<label for="pay_vector_merchant_id"><?php echo __( 'Merchant ID', 'wpsc' ) ?></label>
	</td>
	<td>
		<input type="text" name="PayVector[MerchantID]" id="pay_vector_merchant_id" value="<?php echo get_option( "pay_vector_merchant_id" ) ?>" size="30" />
		<p class=" description">
 			<?php echo __( "MerchantID that you were sent on setting up your account (not your MMS username)", 'wpsc' ) ?>
 		</p>
	</td>
</tr>
<tr>
	<td>
		<label for="pay_vector_merchant_password"><?php echo  __( 'Merchant Password', 'wpsc' ) ?></label>
	</td>
	<td>
		<input type="password" name="PayVector[MerchantPassword]" id="pay_vector_merchant_password" value="<?php echo get_option( "pay_vector_merchant_password" ) ?>" size="16" />
		<p class=" description">
 			<?php echo __( "Merchant Password that you were sent on setting up your account (not your MMS password)", 'wpsc' ) ?>
 		</p>
	</td>
</tr>
<tr>
	<td>
		<label for="pay_vector_card_types"><?php echo __( 'Card Types', 'wpsc') ?></label>
	</td>
	<td>
		<select id="pay_vector_card_types" name="PayVector[CardTypes][]" multiple>
		<?php
			$cardTypes = array(
				"VISA",
				"MasterCard",
				"VISA Electron",
				"Maestro",
				"American Express",
				"Diners Club",
				"SOLO",
				"DELTA",
				"JCB"
			);
			$supportedCardTypes = json_decode(get_option( 'pay_vector_card_types' ));
			
			if(!isset($supportedCardTypes))
			{
				$supportedCardTypes = $cardTypes;
			}
			
			foreach($cardTypes as $cardType)
			{
				if(in_array($cardType, $supportedCardTypes))
				{
					echo "<option value='$cardType' selected>$cardType</option>";
				}
				else
				{
					echo "<option value='$cardType'>$cardType</option>";
				}
			}
		?>
		</select>
		<div class="chosen-spacer" style="width:107px;display:none"></div>
		<p class=" description">
 			<?php echo __( "Card Types to accept", 'wpsc' ) ?>
 		</p>
	</td>
</tr>
<tr>
	<td>
		<label for="pay_vector_capture_method"><?php echo __( 'Capture Method', 'wpsc' ) ?></label>
	</td>
	<td>
		<select id="CaptureMethod" name="PayVector[CaptureMethod]">
		<?php		 
			$currentSelection = get_option( 'pay_vector_capture_method' );
			$hashMethods = array(
				"Direct/API" => "Allows customers to stay on your site throughout the whole of the payment process. This provides a much smoother checkout experience, and keeps the details of the underlying payment processor completely hidden from the customers. The API for this method exposes the full functionality of the payment system. This method requires your system to be able to serve out HTTPS pages, which will likely require a SSL certificate.",
				"Hosted Payment Form" => "A secure payment form which the customer is redirected to during the checkout process. They will complete the order on our system and then be redirected back to the your system with the results of the transaction. Our system allows this payment form to be completely re-skinned so that it closely matches the merchant’s own branding. This method is generally used by merchants who are using a shopping cart that does not support the Direct/API integration method, merchants who cannot host secure (HTTPS) pages or merchants who would like to completely outsource the payment process of their website – usually for PCI compliance reasons"
			);
			
			$counter = 0;
			
			foreach($hashMethods as $method => $comment)
			{
				if($method == $currentSelection || ($currentSelection === false && $counter === 0))
				{
					echo "<option value='$method' title='$comment' selected>$method</option>";
					$counter++;
				}
				else
				{
					echo "<option value='$method' title='$comment'>$method</option>";
				}
			}
		?>
		</select>
		<div class="chosen-spacer" style="width:107px;display:none"></div>
		<p class=" description">
 			<?php echo __( "Method to use to capture card details", 'wpsc' ) ?>
		</p>
	</td>
</tr>
<tr class="hpf-options">
	<td>
		<label for="pay_vector_hash_method"><?php echo __( 'Hash Method', 'wpsc' ) ?></label>
	</td>
	<td>
		<select name="PayVector[HashMethod]">
		<?php		 
			$currentSelection = get_option( 'pay_vector_hash_method' );
			$hashMethods = array(
				"MD5" => "We suggest you only use MD5 if your server does not support any of the other algorithms",
				"HMACMD5" => "We suggest you only use HMACSMD5 if your server does not support SHA1",
				"SHA1" => "Default MMS option, more secure than the MD5 algorithms",
				"HMACSHA1" => "HMACSHA1 is the most secure setting, it is recommended you use this if your server supports it"
			);
			
			$counter = 0;
			
			foreach($hashMethods as $method => $comment)
			{
				if($method == $currentSelection || ($currentSelection === false && $counter === 0))
				{
					echo "<option value='$method' title='$comment' selected>$method</option>";
					$counter++;
				}
				else
				{
					echo "<option value='$method' title='$comment'>$method</option>";
				}
			}
		?>
		</select>
		<div class="chosen-spacer" style="width:107px;display:none"></div>
		<p class=" description">
 			<?php echo __( "Hash method - this must match the method set in the MMS", 'wpsc' ) ?>
		</p>
	</td>
</tr>
<tr class="hpf-options">
	<td>
		<label for="pay_vector_result_delivery_method"><?php echo  __( 'Result delivery method', 'wpsc' ) ?></label>
	</td>
	<td>
		<select name="PayVector[ResultDeliveryMethod]" id="pay_vector_result_delivery_method">
		<?php		 
			$currentSelection = get_option( 'pay_vector_result_delivery_method' );
			$resultDeliveryMethods = array(
				"POST" => "Choosing the POST method will deliver the full results via the customer's browser as a form post back to the CallbackURL. The downside is, if you do not have an SSL certificate then most modern browsers throw a security warning to the customer explaining that sensitive information is being passed over to an insecure connection. We do not send sensitive information back, but the browsers are trying to safeguard the customer. As a result, we show the customer a dialog informing them of the reason why they are about to see a security warning and how to handle it.",
				"SERVER_PULL" => "When chosen, the results are PULLED FROM the payment form by the your system after the customer has been redirected back to the website. This has the advantage of getting around the modern security warning if you’re not using HTTPS (Secure Connection).",
			);
			
			$counter = 0;
			
			foreach($resultDeliveryMethods as $method => $comment)
			{
				if($method == $currentSelection || ($currentSelection === false && $counter === 0))
				{
					echo "<option value='$method' title='$comment' selected>$method</option>";
					$counter++;
				}
				else
				{
					echo "<option value='$method' title='$comment'>$method</option>";
				}
			}
		?>
		</select>
		<p class=" description">
 			<?php echo __( "Should be set to POST if you have an SSL certificate or SERVER_PULL otherwise", 'wpsc' ) ?>
 		</p>
	</td>
</tr>
<tr class="hpf-options">
	<td>
		<label for="pay_vector_pre_shared_key"><?php echo  __( 'Pre Shared Key', 'wpsc' ) ?></label>
	</td>
	<td>
		<input type="text" name="PayVector[PreSharedKey]" id="pay_vector_pre_shared_key" value="<?php echo get_option( 'pay_vector_pre_shared_key' ) ?>" size="16" />
		<p class=" description">
 			<?php echo __( "The Pre Shared Key - can be found in the Account Settings section of the PayVector MMS", 'wpsc' ) ?>
 		</p>
	</td>
</tr>
<script>
    jQuery(document).ready(function($) {
    	$("select").chosen();
		$(".chosen-spacer").css("display", "inline-block");
		$("#pay_vector_currency_code_chosen").css("width", "80px");
		$(".chosen-container").tooltip({ position: { my: "left+10 center", at: "right center" } });
		$(".chosen-container-multi .chosen-choices li.search-field input[type='text']").css("height", "20px");
		$(".chosen-container-multi").css("width", "100%");
		$(".chosen-container-multi").css("min-width", "150px");
		$(".widefat td").css("overflow", "visible");
		
		var hpfOptions = $(".hpf-options");

		$("#CaptureMethod").change(function() {
			if($(this).val() === "Direct/API")
			{
				hpfOptions.hide();
			}
			else
			{
				hpfOptions.show();
			}
		});
		
		if($("#CaptureMethod").val() === "Direct/API")
		{
			hpfOptions.hide();
		}
		
	});
</script>