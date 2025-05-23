
<div style="clear: both; display: block;">
    <div class="vmpayment_cardinfo payvector_card" style="display: block; margin-top: 10px;">
        <?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_COMPLETE_FORM') . ' ' . $sandboxMessage; ?>

        <input type="hidden" name="payment_type" value="new_card" />
        <!-- 3DSv2 Hidden Fields -->
        <input type="hidden" name="browserJavaEnabled" id="browserJavaEnabled" value="">
        <input type="hidden" name="browserLanguage" id="browserLanguage" value="">
        <input type="hidden" name="browserColorDepth" id="browserColorDepth" value="">
        <input type="hidden" name="browserScreenHeight" id="browserScreenHeight" value="">
        <input type="hidden" name="browserScreenWidth" id="browserScreenWidth" value="">
        <input type="hidden" name="browserTZ" id="browserTZ" value="">
        <input type="hidden" name="browserUserAgent" id="browserUserAgent" value="">

        <div class="mb-3">
            <label class="form-label" for="cc_cardholdername_<?php echo $method->virtuemart_paymentmethod_id; ?>"><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_CARDHOLDERNAME'); ?></label>
            
            <input type="text" class="form-control"
                           id="cc_cardholdername_<?php echo $method->virtuemart_paymentmethod_id; ?>"
                           name="cc_cardholdername_<?php echo $method->virtuemart_paymentmethod_id; ?>"
                           value="<?php echo htmlspecialchars($PvCardName, ENT_QUOTES, 'UTF-8'); ?>"
                           autocomplete="off" />
                <div id="cc_cardholdername_errormsg_<?php echo $method->virtuemart_paymentmethod_id; ?>"></div>                           
        </div>

        <div class="mb-3">
            <label class="form-label" for="cc_number_<?php echo $method->virtuemart_paymentmethod_id; ?>"><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_CCNUM'); ?></label>
            <input type="text" class="form-control"
                           id="cc_number_<?php echo $method->virtuemart_paymentmethod_id; ?>"
                           name="cc_number_<?php echo $method->virtuemart_paymentmethod_id; ?>"
                           value="<?php echo htmlspecialchars($this->cardDetails->getCardNumber(), ENT_QUOTES, 'UTF-8'); ?>"
                           autocomplete="off"
                           onchange="validateCreditCard(<?php echo $method->virtuemart_paymentmethod_id; ?>, this)" /> 
                <div id="cc_cardnumber_errormsg_<?php echo $method->virtuemart_paymentmethod_id; ?>"></div>                          
        </div>



        <div class="mb-3">
            <label class="form-label" for="cc_expire_month_<?php echo $method->virtuemart_paymentmethod_id; ?>"><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_EXDATE'); ?></label>
             
             <table border="0" cellspacing="0" cellpadding="2" width="100%">
             <tr>
                <?php
                    echo "<td>";
                    echo shopfunctions::listMonths('cc_expire_month_' . $method->virtuemart_paymentmethod_id, $PvExpMonth ?: '');
                    echo "</td><td>";
                    echo " / ";
                    echo "</td><td>";
                    echo shopfunctions::listYears('cc_expire_year_' . $method->virtuemart_paymentmethod_id, "20" . ($PvExpYear ?: ''));
                    echo "</td>";
                    ?>
              </tr>                      
              </table>
                    <div id="cc_expiredate_errormsg_<?php echo $method->virtuemart_paymentmethod_id; ?>"></div>           
                    
        </div>

        <div class="mb-3">
            <label class="form-label" for="cc_cvv_<?php echo $method->virtuemart_paymentmethod_id; ?>"><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_CVV2'); ?></label>
            <input type="text" class="form-control"
                           id="cc_cvv_<?php echo $method->virtuemart_paymentmethod_id; ?>"
                           name="cc_cvv_<?php echo $method->virtuemart_paymentmethod_id; ?>"
                           maxlength="4" size="5"
                           value="<?php echo htmlspecialchars($this->cardDetails->getCV2(), ENT_QUOTES, 'UTF-8'); ?>"
                           autocomplete="off" />                
        </div>

        <div class="mb-3">
            <label class="form-label" for="cc_issuenum_<?php echo $method->virtuemart_paymentmethod_id; ?>"><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_CARDISSUE') ?></label>
            <input type="text" class="form-control" maxlength="4" size="5"
				   id="cc_issuenum_<?php echo $method->virtuemart_paymentmethod_id ?>"
				   name="cc_issuenum_<?php echo $method->virtuemart_paymentmethod_id ?>"
				   value="<?php echo $this->cardDetails->getIssueNumber() ?>"
				   autocomplete="off" />             
            <div id="cc_issuenum_errormsg_<?php echo $method->virtuemart_paymentmethod_id ?>"></div>
        </div>

    
    </div>
</div>

<script>
jQuery(document).ready(function ($) {
    var newCard = $('#NewCard');
    var savedCard = $('#SavedCard');
    var newCardDetails = $('#NewCardDetails');
    var savedCardDetails = $('#SavedCardDetails');

    function toggleCardDetails() {
        if (newCard.is(':checked')) {
            savedCardDetails.hide();
            newCardDetails.show();
        } else {
            newCardDetails.hide();
            savedCardDetails.show();
        }
    }

    $('.PaymentType').on('change', toggleCardDetails);

    var isNewCard = <?php echo ($this->paymentType == SaleType::NewSale) ? 'true' : 'false'; ?>;

    if (isNewCard) {
        newCard.prop('checked', true);
    } else {
        savedCard.prop('checked', true);
    }

    toggleCardDetails();

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

    document.querySelectorAll(".payvector_card *").forEach(element => {
    if (element.tagName.trim().toLowerCase() === "i n p u t".replace(/\s+/g, '') && (element.type.toLowerCase() !== "radio") ) { 
        element.className = "form-control pv-control";
    }
    if (element.tagName.toLowerCase() === "select") { 
        element.className = "pv-select";
    }
    });

});

</script>
<style>
.pv-select {    
    width: 100%;
	padding: .2rem 0.2rem;	
}
.pv-control {        
	padding: .2rem 0.2rem;	
}
</style>