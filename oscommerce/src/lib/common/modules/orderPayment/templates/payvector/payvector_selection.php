<?php
/**
 * PayVector Selection Template
 */

$requested_paytype = \Yii::$app->request->get('paytype');
$selected_card_id = 'new';
$has_saved_cards = false;
$first_saved_card_id = null;

foreach ($cards_array as $card) {
    if ($card['id'] !== 'new') {
        $has_saved_cards = true;
        if ($first_saved_card_id === null) {
            $first_saved_card_id = $card['id'];
        }
    }
}


if (!empty($requested_paytype)) {    
    
    $is_valid_choice = false;
    foreach ($cards_array as $card) {
        if ($card['id'] == $requested_paytype) {
            $is_valid_choice = true;
            break;
        }
    }
    
    if ($requested_paytype == 'new') $is_valid_choice = true;
    
    if ($is_valid_choice) {
        $selected_card_id = $requested_paytype;
    } else {    
        $selected_card_id = $has_saved_cards ? $first_saved_card_id : 'new';
    }
    
} elseif ($has_saved_cards) {
    
    $selected_card_id = $first_saved_card_id;
} else {
    
    $selected_card_id = 'new';
}


$saved_card_options = '';
foreach ($cards_array as $card) {
    $selected = ($card['id'] == $selected_card_id) ? 'selected' : '';
    $saved_card_options .= '<option value="' . $card['id'] . '" ' . $selected . '>' . $card['text'] . '</option>';
}

$months_options = '';
foreach ($months as $m) {
    $months_options .= '<option value="' . $m['id'] . '">' . $m['text'] . '</option>';
}

$years_options = '';
foreach ($years as $y) {
    $years_options .= '<option value="' . $y['id'] . '">' . $y['text'] . '</option>';
}

$payment_error = \Yii::$app->request->get('payment_error');
$error_message = \Yii::$app->request->get('error');
?>

<?php if (!empty($payment_error) && $payment_error === 'payvector' && !empty($error_message)): ?>
<div class="alert alert-danger payvector-error-message" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px 20px; margin-bottom: 20px; border-radius: 4px;">
    <strong>Payment Error:</strong> <?php echo htmlspecialchars(urldecode($error_message)); ?>
</div>
<?php endif; ?>

<div id="payvector-payment-module" class="clearfix">    
    <input type="hidden" name="payvector_browser_java_enabled" id="browserJavaEnabled" value="">
    <input type="hidden" name="payvector_browser_language" id="browserLanguage" value="">
    <input type="hidden" name="payvector_browser_color_depth" id="browserColorDepth" value="">
    <input type="hidden" name="payvector_browser_screen_height" id="browserScreenHeight" value="">
    <input type="hidden" name="payvector_browser_screen_width" id="browserScreenWidth" value="">
    <input type="hidden" name="payvector_browser_tz" id="browserTZ" value="">
    <input type="hidden" name="payvector_browser_user_agent" id="browserUserAgent" value="">

    
    <?php foreach ($cards_array as $card): ?>
        <?php if ($card['id'] == 'new') continue; ?>
        <div class="form-group" style="margin-bottom: 10px;">
            <label>
                <input type="radio" name="payvector_saved_card" value="<?php echo $card['id']; ?>" class="payvector-card-selector" <?php echo ($card['id'] == $selected_card_id ? 'checked' : ''); ?>>
                <?php echo $card['text']; ?>
            </label>
        </div>        
    <?php endforeach; ?>

    
    <div class="payvector-cvv form-group" style="display:none; margin-left: 20px;">
        <label for="payvector_saved_cvv" class="control-label" style="display: block; margin-bottom: 5px;">CVV <small class="text-muted">(for selected card)</small></label>
        <input type="text" name="payvector_saved_cc_cvv" id="payvector_saved_cvv" size="4" maxlength="4" minlength="3" autocomplete="off" class="form-control" style="width: 80px;">
    </div>

    
    <div class="form-group" style="margin-top: 15px;">
        <label>
            <input type="radio" name="payvector_saved_card" value="new" class="payvector-card-selector" <?php echo ('new' == $selected_card_id ? 'checked' : ''); ?>>
            New Card
        </label>
    </div>


    <?php if ($is_direct_api): ?>
    <div class="payvector-card-details">
        <div class="control-group form-group">
            <label for="payvector_cc_owner" class="control-label" style="display: block; margin-bottom: 5px;">Cardholder Name</label>
            <input type="text" name="payvector_cc_owner" id="payvector_cc_owner" value="<?php echo htmlspecialchars($billing_name); ?>" class="form-control">
        </div>
        
        <div class="control-group form-group">
            <label for="payvector_cc_number" class="control-label" style="display: block; margin-bottom: 5px;">Card Number</label>
            <input type="text" name="payvector_cc_number" id="payvector_cc_number" autocomplete="off" class="form-control">
        </div>
        
        <div class="control-group form-group">
            <label for="payvector_cc_expires_month" class="control-label" style="display: block; margin-bottom: 5px;">Expiry Date</label>
            <div class="form-inline">
                <select name="payvector_cc_expires_month" id="payvector_cc_expires_month" class="form-control" style="width: auto; display: inline-block;">
                    <?php echo $months_options; ?>
                </select>
                &nbsp;/&nbsp;
                <select name="payvector_cc_expires_year" id="payvector_cc_expires_year" class="form-control" style="width: auto; display: inline-block;">
                    <?php echo $years_options; ?>
                </select>
            </div>
        </div>

        <div class="control-group form-group">
            <label for="payvector_new_cc_cvv" class="control-label" style="display: block; margin-bottom: 5px;">CVV</label>
            <input type="text" name="payvector_new_cc_cvv" id="payvector_new_cc_cvv" size="4" maxlength="4" minlength="3" autocomplete="off" class="form-control" style="width: 80px;">
        </div>
    </div>
    <?php endif; ?>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function($) {
    function togglePayVectorFields() {
        var selectedValue = $('input[name="payvector_saved_card"]:checked').val();
        var isNew = (selectedValue === 'new');
        
        var $cardDetails = $('.payvector-card-details');
        var $cvvContainer = $('.payvector-cvv');
        var $cvvStored = $('#payvector_saved_cvv');
        
        
        function setVisibility($el, show) {
            if ($el.length === 0) return;
            var $row = $el.closest('tr');
            if ($row.length === 0) $row = $el.closest('.form-group');
            
            if (show) {
                $el.show();
                if ($row.length) $row.show();
            } else {
                $el.hide();
                if ($row.length) $row.hide();
            }
        }

        if (isNew) {
            setVisibility($cardDetails, true);
            setVisibility($cvvContainer, false);
            
            $cvvStored.prop('disabled', true).prop('required', false);
            $cardDetails.find('input, select').prop('disabled', false).prop('required', true);            
        } else {
            setVisibility($cardDetails, false);
            setVisibility($cvvContainer, true);
            
            $cvvStored.prop('disabled', false).prop('required', true);
            $cardDetails.find('input, select').prop('disabled', true).prop('required', false);
        }
    }

    function togglePayVectorModule() {
        
        var $selectedPayment = $('input[name="payment"]:checked');
        var $moduleContainer = $('#payvector-payment-module');
        
        if ($moduleContainer.length === 0) return;

        if ($selectedPayment.val() === 'payvector') {
            $moduleContainer.show();
            togglePayVectorFields(); 
        } else {
            $moduleContainer.hide();
        }
    }

    function get3DSv2Params() {
        if (document.getElementById("browserJavaEnabled")) document.getElementById("browserJavaEnabled").value = navigator.javaEnabled();
        if (document.getElementById("browserLanguage")) document.getElementById("browserLanguage").value = navigator.language || navigator.userLanguage;
        if (document.getElementById("browserColorDepth")) document.getElementById("browserColorDepth").value = screen.colorDepth;
        if (document.getElementById("browserScreenHeight")) document.getElementById("browserScreenHeight").value = screen.height;
        if (document.getElementById("browserScreenWidth")) document.getElementById("browserScreenWidth").value = screen.width;
        if (document.getElementById("browserTZ")) document.getElementById("browserTZ").value = new Date().getTimezoneOffset();
        if (document.getElementById("browserUserAgent")) document.getElementById("browserUserAgent").value = navigator.userAgent;
    }

    $(document).ready(function() {
        
        $(document).on('change', 'input[name="payvector_saved_card"]', togglePayVectorFields);
        $(document).on('change', 'input[name="payment"]', togglePayVectorModule);
        
        
        togglePayVectorModule();
        togglePayVectorFields();
        get3DSv2Params();
        
        
        setTimeout(togglePayVectorModule, 500);
        setTimeout(togglePayVectorFields, 500);
    });
})(jQuery);
</script>
