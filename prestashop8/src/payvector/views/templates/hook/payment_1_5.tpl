<div id="payvector-payment-module" class="row">
	<div class="col-xs-12">
		<div id="payvector-payment-module-inner">
			<span id="payvector-logo-link">Pay by Credit or Debit Card</span>
			<style>
			#payvector-payment-module *, #payvector-payment-module *:before, #payvector-payment-module *:after {
				box-sizing: border-box;
			}
			#payvector-payment-module {
				margin-left: -15px;
				margin-right: -15px;
			}
			#payvector-payment-module:before,
			#payvector-payment-module:after,
			#payvector-payment-module .form-row:before,
			#payvector-payment-module .form-row:after {
				content: " ";
				/* 1 */
				display: table;
				/* 2 */
			}
			#payvector-payment-module:after, #payvector-payment-module .form-row:after {
				clear: both;
			}
			#payvector-payment-module .col-xs-4,
			#payvector-payment-module .col-xs-8,
			#payvector-payment-module .col-xs-12 {
				position: relative;
				min-height: 1px;
				padding-left: 15px;
				padding-right: 15px;
				float: left;
			}
			#payvector-payment-module .col-xs-2 {
				width: 16.66667%;
			}
			#payvector-payment-module .col-xs-4 {
				width: 33.33333%;
			}
			#payvector-payment-module .col-xs-6 {
				width: 50%;
			}
			#payvector-payment-module .col-xs-8 {
				width: 66.66666%;
			}
			#payvector-payment-module .col-xs-12 {
				width: 100%;
			}
			@media (max-width: 480px) {
				#payvector-payment-module label {
					font-size: 12px;
				}
			}
			@media (min-width: 992px) {
				#payvector-payment-module .col-md-6 {
					width: 50%;
				}
			}
			#payvector-payment-module select {
				width: 31.3333%;
			}
			.dropdown-spacer {
				float: left;
				min-height: 1px;
				width: 4%;
			}
			#payvector-logo-link {
				cursor: pointer;
			}
			#payvector-hidden {
				border-top: 1px solid #ddd;
				padding-top: 1em;
				padding-bottom: 1em;
			}
			#payvector-logo-link {
				background: url("modules/payvector/img/logo-small.png") no-repeat scroll 15px 15px;
				display: block;
				font-size: 17px;
				font-weight: bold;
				letter-spacing: -1px;
				line-height: 23px;
				padding: 33px 40px 34px 99px;
				position: relative;
			}
			#payvector-payment-module-inner {
				background-color: #fbfbfb;
				border: 1px solid #d6d4d4;
				border-radius: 4px;
				color: #333333;
			}
			.form-row {
				margin-bottom: .5em;
			}
			.form-row input {
				border-radius: 1px;
				border: 1px solid #c0c0c0;
				width: 66.6667%;
			}
			.form-row label {
				text-align: right;

			}
			.form-row select {
				float: left;
				width: 25%;
			}
			</style>
			<script>
				jQuery(document).ready(function($) {
					var $payvectorHidden = $('#payvector-hidden');
					var $payvectorLogoLink = $('#payvector-logo-link');
					$payvectorHidden.hide();

					$('#payvector-logo-link').click(function(e) {
						$payvectorHidden.slideDown();
						$payvectorLogoLink.css("cursor", "auto");
					});

				});
			</script>
			<div id="payvector-hidden">
				<form method="post" action="{$link->getModuleLink('payvector', 'payment',array(),'true')|escape:'htmlall':'UTF-8'}" id="form-settings" class="defaultForm form-horizontal">
					{if empty($cross_reference)}
						<input id="payvector-new-card" type="hidden" name="payvector_payment_type" value="new_card">
						{if $capture_method == "Hosted Payment Form"}
						<p class="col-xs-12">{l s='On clicking Submit you will be redirected to our secure payment form' mod='payvector'}</p>
						{/if}
					{else}
					<div class="form-row row">
						<label class="col-xs-4" for="payvector-stored-card">{l s='Use saved' mod='payvector'} {$card_type} {l s='card' mod='payvector'}{if !empty($card_last_four)} xxxx-{$card_last_four}{/if}</label>
						<div class="col-xs-8">
							<input id="payvector-stored-card" type="radio" name="payvector_payment_type" value="stored_card" checked>
						</div>
					</div>
					<div class="form-row row">
						{if $capture_method == "Hosted Payment Form"}
						<label class="col-xs-4" for="payvector-new-card">{l s='Enter new card details on our secure payment form' mod='payvector'}</label>
						{else}
						<label class="col-xs-4" for="payvector-new-card">{l s='Enter new card details' mod='payvector'}</label>
						{/if}
						<div class="col-xs-8">
							<input id="payvector-new-card" type="radio" name="payvector_payment_type" value="new_card">
						</div>
					</div>
					{/if}
					<div class="form-row row payvector-card-details">
						<label class="col-xs-4" for="payvector_cc_number">{l s='Credit Card Number' mod='payvector'} *</label>
						<div class="col-xs-8">
							<input type="text" id="payvector_cc_number" name="payvector_cc_number" value="">
						</div>
					</div>
					<div class="form-row row payvector-card-details">
						<label class="col-xs-4" for="payvector_cc_expiry_date">{l s='Expiry Date Month/Year' mod='payvector'} *</label>
						<div class="col-xs-8">
							<select name='payvector_cc_expiry[month]'>
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
							<div class="dropdown-spacer"></div>
							<select name='payvector_cc_expiry[year]'>
								{foreach from=$expiry_year item=year}
									echo "<option value='{$year}'>{$year}</option>";
								{/foreach}
							</select>
						</div>
					</div>
					<div class="form-row row payvector-card-details">
						<label class="col-xs-4" for="payvector_issue_number">{l s='Issue Number' mod='payvector'}</label>
						<div class="col-xs-8">
							<input type="text" value='' maxlength='4' id="payvector_issue_number" name="payvector_issue_number">
						</div>
					</div>
					<div class="form-row row payvector-cvv">
						<label class="col-xs-4" for="payvector_cc_cvv">{l s='CVV' mod='payvector'} *</label>
						<div class="col-xs-8">
							<input type="text" value='' maxlength='4' id="payvector_cc_cvv" name="payvector_cc_cvv">
						</div>
					</div>
					<div class="form-row row" id="payvector-required">
						<span class="col-xs-12"><strong>*</strong> {l s='required field' mod='payvector'}</span>
					</div>
					<center>
						<input type="submit" name="payvector_process_payment" value="{l s='Submit' mod='payvector'}" class="btn btn-default">
					</center>
				</form>

				<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
				<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css" />
				<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
				<script>
					jQuery1_11_2 = jQuery.noConflict(true);
					jQuery1_11_2(document).on("ready ajaxComplete", function() {
						var $paymentType = jQuery1_11_2("input[name='payvector_payment_type']");
						var $storedCard = jQuery1_11_2("#payvector-stored-card");
						var $newCard = jQuery1_11_2("#payvector-new-card");
						var $cardDetailsInput = jQuery1_11_2(".payvector-card-details");
						var captureMethod = "{$capture_method}";
						var $cvvContainer = jQuery1_11_2(".payvector-cvv");
						var $cvv = jQuery1_11_2("#payvector_cc_cvv");
						var $requiredField = jQuery1_11_2("#payvector-required");
						var $payvectorTable = jQuery1_11_2('#payvector-table');

						if(captureMethod === "Hosted Payment Form")
						{
							$cardDetailsInput.hide();

							if($paymentType.val() === "new_card")
							{
								$cvvContainer.hide();
								$requiredField.hide();
								$cvv.prop('disabled', true);
							}

							$paymentType.click(function() {
								if(jQuery1_11_2(this).val() === "new_card")
								{
									$payvectorTable.hide();
									$cvvContainer.hide();
									$requiredField.hide();
									$cvv.prop('disabled', true);
								}
								else
								{
									$payvectorTable.show();
									$cvv.prop('disabled', false);
									$cvvContainer.show();
									$requiredField.show();
								}
							});
						}
						else
						{
							if($storedCard.is(":checked"))
							{
								$cardDetailsInput.hide();
							}

							$paymentType.change(function() {
								if(jQuery1_11_2(this).val() === "new_card")
								{
									$cardDetailsInput.show();
								}
								else
								{
									$cardDetailsInput.hide();
								}
							});
						}

						//$cvv.tooltip({ position: { my: "center top+5", at: "right bottom" }, content: '<img src="modules/payvector/img/CVV.jpg" />' });

					});
				</script>
			</div>
		</div>
	</div>
</div>