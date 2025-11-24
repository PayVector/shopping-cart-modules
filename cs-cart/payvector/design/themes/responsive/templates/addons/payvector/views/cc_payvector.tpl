{script src="js/lib/inputmask/jquery.inputmask.min.js"}
{script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}
{script src="js/addons/payvector/payvector.js"}

{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

<div id="payvector-payment-module" data-ca-payvector="1" class="clearfix ty-credit-card cm-cc_form_{$id_suffix}">
    
    
    <input type="hidden" name="pv_info[browserJavaEnabled]" id="browserJavaEnabled">
    <input type="hidden" name="pv_info[browserLanguage]" id="browserLanguage">
    <input type="hidden" name="pv_info[browserColorDepth]" id="browserColorDepth">
    <input type="hidden" name="pv_info[browserScreenHeight]" id="browserScreenHeight">
    <input type="hidden" name="pv_info[browserScreenWidth]" id="browserScreenWidth">
    <input type="hidden" name="pv_info[browserTZ]" id="browserTZ">
    <input type="hidden" name="pv_info[browserUserAgent]" id="browserUserAgent">
    <input type="hidden" name="capture_method" id="capture_method" value="{$pv_cc.capture_method}">

    
    {if $pv_cc.cross_reference}
        <div class="ty-control-group">
            <label class="ty-control-group__title">
                <input type="radio" id="payvector-stored-card" name="pv_info[payvector_payment_type]" value="stored_card" checked  onclick="payvectorTogglePaymentType(this.value);">
                {__("addons.payvector.use_saved_card")} {$pv_cc.card_type}{if $pv_cc.card_last_four} xxxx-{$pv_cc.card_last_four}{/if}
            </label>
        </div>

        <div class="ty-control-group payvector-cvv">
            <label for="sc_payvector_cc_cvv2" class="ty-control-group__title cm-required">{__("cvv2")}</label>
            <input type="text" id="sc_payvector_cc_cvv2" name="payment_info[cvv2]" size="4" maxlength="4" class="ty-credit-card__cvv-field-input">
        </div>

        <div class="ty-control-group">
            <label class="ty-control-group__title">
                <input type="radio" id="payvector-new-card" name="pv_info[payvector_payment_type]" value="new_card"  onclick="payvectorTogglePaymentType(this.value);">
                {if $pv_cc.capture_method == "hosted"}
                    {__("addons.payvector.enter_new_card_details_on_secure_form")}
                {else}
                    {__("addons.payvector.enter_new_card_details")}
                {/if}
            </label>
        </div>
    {else}
        <input type="hidden" name="pv_info[payvector_payment_type]" value="new_card"  onclick="payvectorTogglePaymentType(this.value);">
        {if $pv_cc.capture_method == "hosted"}
            <p class="ty-muted">{__("addons.payvector.enter_new_card_details_on_secure_form")}</p>
        {/if}
    {/if}
    {if $pv_cc.capture_method == "direct"}
    {assign var="hide_card_details_style" value=""}
     {if $pv_cc.cross_reference}
        {assign var="hide_card_details_style" value="display: none;"}
     {/if}
    <div class="clearfix ty-credit-card payvector-card-details" style="{$hide_card_details_style}">
    <div class="ty-credit-card__control-group ty-control-group payvector-card-details" style="{$hide_card_details_style}">
        <label for="credit_card_name_{$id_suffix}" class="ty-control-group__title cm-required">{__("cardholder_name")}</label>
        <input size="35" type="text" id="credit_card_name_{$id_suffix}" name="payment_info[cardholder_name]" value="{$pv_cc.cardholder}" class="cm-cc-name ty-credit-card__input ty-uppercase">
    </div>

    <div class="ty-credit-card__control-group ty-control-group payvector-card-details" style="{$hide_card_details_style}">
        <label for="credit_card_number_{$id_suffix}" class="ty-control-group__title cm-required cm-cc-number cc-number_{$id_suffix}">
            {__("card_number")}
        </label>
        <input type="text" id="credit_card_number_{$id_suffix}" name="payment_info[card_number]" value="" class="ty-credit-card__input cm-autocomplete-off">
        <ul class="ty-cc-icons cm-cc-icons cc-icons_{$id_suffix}">
            <li class="ty-cc-icons__item cc-default cm-cc-default"><span class="ty-cc-icons__icon default">&nbsp;</span></li>
            <li class="ty-cc-icons__item cm-cc-visa"><span class="ty-cc-icons__icon visa">&nbsp;</span></li>
            <li class="ty-cc-icons__item cm-cc-mastercard"><span class="ty-cc-icons__icon mastercard">&nbsp;</span></li>
            <li class="ty-cc-icons__item cm-cc-amex"><span class="ty-cc-icons__icon american-express">&nbsp;</span></li>
            <li class="ty-cc-icons__item cm-cc-discover"><span class="ty-cc-icons__icon discover">&nbsp;</span></li>
        </ul>
    </div>

    
    <div class="ty-credit-card__control-group ty-control-group payvector-card-details" style="{$hide_card_details_style}">
        <label for="credit_card_month_{$id_suffix}" class="ty-control-group__title cm-required cm-cc-date cc-date_{$id_suffix}">
            {__("valid_thru")}
        </label>
        <label for="credit_card_year_{$id_suffix}" class="hidden"></label>
        <input type="text" id="credit_card_month_{$id_suffix}" name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="ty-credit-card__input-short">&nbsp;/&nbsp;
        <input type="text" id="credit_card_year_{$id_suffix}" name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="ty-credit-card__input-short">
    </div>

    
    

    
    <div class="ty-credit-card__control-group ty-control-group payvector-card-details" style="{$hide_card_details_style}">   
        <label for="credit_card_cvv2" class="ty-control-group__title cm-required cm-cc-cvv2 cc-cvv2_{$id_suffix} cm-autocomplete-off">{__("cvv2")}</label>
        <input type="text" id="credit_card_cvv2" name="payment_info[cvv2]" size="4" maxlength="4" class="ty-credit-card__cvv-field-input">
    </div>
  </div>  
    {/if}
</div>