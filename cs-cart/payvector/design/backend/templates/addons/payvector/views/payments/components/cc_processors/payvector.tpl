<div class="control-group">
    <label class="control-label" for="merchant_id">{__("merchant_id")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_id]" id="merchant_id" value="{$processor_params.merchant_id}"  size="60">
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="merchant_password">
    {__("addons.payvector.merchant_pass")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_password]" id="merchant_password" value="{$processor_params.merchant_password}"  size="60">
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="capture_method">{__("addons.payvector.capture_method")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][capture_method]" id="capture_method" onchange="toggleHformSettings(this.value)">
            <option value="direct" {if $processor_params.capture_method == "direct"}selected="selected"{/if}>{__("addons.payvector.direct_api")}</option>
            <option value="hosted" 
            {if !$processor_params.capture_method || $processor_params.capture_method|trim == "" || $processor_params.capture_method == "hosted"}
                selected="selected"
            {/if}>
            {__("addons.payvector.hform")}
            </option>
        </select>
    </div>
</div>

<div class="control-group" id='hform_settings'>
<div class="control-group">
    <label class="control-label" for="presharedkey">{__("addons.payvector.presharedkey")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][presharedkey]" id="presharedkey" value="{$processor_params.presharedkey}"  size="60">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="hash_method">{__("addons.payvector.hash_method")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][hash_method]" id="hash_method">            
            <option value="MD5" {if $processor_params.hash_method == "MD5"}selected="selected"{/if}>MD5</option>
            <option value="HMACMD5" {if $processor_params.hash_method == "HMACMD5"}selected="selected"{/if}>HMACMD5</option>
            <option value="SHA1" 
                {if !$processor_params.hash_method || $processor_params.hash_method|trim == "" || $processor_params.hash_method == "SHA1"}selected="selected"{/if}>
                SHA1
            </option>
            <option value="HMACSHA1" {if $processor_params.hash_method == "HMACSHA1"}selected="selected"{/if}>HMACSHA1</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="result_delivery_method">{__("addons.payvector.result_delivery_method")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][result_delivery_method]" id="result_delivery_method">            
            <option value="POST" {if $processor_params.result_delivery_method == "POST"}selected="selected"{/if}>POST</option>
            <option value="SERVER_PULL" {if $processor_params.result_delivery_method == "SERVER_PULL"}selected="selected"{/if}>SERVER_PULL</option>            
        </select>
    </div>
</div>
</div>
{literal}
<script>
function toggleHformSettings(value) {

    var $ = Tygh.$;

    if (value === "direct") {
        $("#hform_settings").hide();
    } else {
        $("#hform_settings").show();
    }
}

// Run on page load
Tygh.$(document).ready(function() {
    toggleHformSettings(Tygh.$("#capture_method").val());
});
</script>
{/literal}
