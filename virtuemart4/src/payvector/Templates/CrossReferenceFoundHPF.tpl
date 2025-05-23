<br />
<span class="vmpayment_cardinfo huf">
	<?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_COMPLETE_FORM') . ' ' . $sandboxMessage ?>
	
	<input type="hidden" name="capture_method" value="HPF" >

	<table border="0" cellspacing="0" cellpadding="2" width="100%">
		<tr valign="middle">
			<td nowrap width="10%">
				<label for="payment_type"><?php echo JText::sprintf('VMPAYMENT_' . $this->paymentElementUppercase . '_SAVED_CARD_HPF', $this->creditCard->type); ?></label>
			</td>
			<td>
				<input id="SavedCard" class="PaymentType" type="radio" name="payment_type" value="stored_card"
				
				<?php
				if( $this->paymentType === SaleType::CrossReferenceSale )
				{
					echo "checked";
				}
				?>

				>
			</td>
		</tr>
		<tr valign="middle">
			<td nowrap width="10%">
				<label for="payment_type"><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_CARD_NEW_CARD') ?></label>
			</td>
			<td>
				<input id="NewCard" class="PaymentType" type="radio" name="payment_type" value="new_card"

				<?php
				if( $this->paymentType === SaleType::NewSale )
				{
					echo "checked";
				}
				?>

				>
			</td>
		</tr>
		<tr id="SavedCardDetails">
			<td nowrap width="10%" align="right">
				<label for="cc_cvv"><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_CVV2') ?></label>
			</td>
			<td>
				<input type="text" class="form-control" id="cc_cvv_saved_<?php echo $method -> virtuemart_paymentmethod_id ?>" name="cc_cvv_saved_<?php echo $method -> virtuemart_paymentmethod_id ?>" maxlength="4" size="5" value="<?php echo $this->cardDetails->getCV2() ?>" autocomplete="off" />

				<span class="hasTip" title="<?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_WHATISCVV') ?>::<?php echo JText::sprintf("VMPAYMENT_PAYVECTOR_WHATISCVV_TOOLTIP", $cvvImages) ?> ">
					<?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_WHATISCVV') ?>
				</span>
			</td>
		</tr>
		<tr id="NewCardDetails"
		<?php
		if( $this->paymentType === SaleType::CrossReferenceSale )
		{
			echo 'style="display:none"';
		}
		?>
		>
			<td colspan="2">
				<?php echo Jtext::_('VMPAYMENT_' . $this->paymentElementUppercase . '_NEW_CARD_HPF'); ?>
			</td>
		</tr>
	</table>
</span>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var newCard = document.getElementById("NewCard");
        var savedCardDetails = document.getElementById("SavedCardDetails");
        var paymentTypeButtons = document.querySelectorAll(".PaymentType");

        function toggleSavedCardDetails() {
            if (newCard.checked) {
                savedCardDetails.style.display = "none";
            } else {
                savedCardDetails.style.display = "block";
            }
        }

        // Initial check on page load
        toggleSavedCardDetails();

        // Add event listeners to all elements with class 'PaymentType'
        paymentTypeButtons.forEach(function (button) {
            button.addEventListener("click", toggleSavedCardDetails);
        });
    });
</script>
