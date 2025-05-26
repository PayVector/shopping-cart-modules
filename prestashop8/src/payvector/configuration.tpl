<form method="post" action="{$form_url}" id="form-settings" class="defaultForm form-horizontal">
	<fieldset>
		<legend>
			<img src="../img/admin/contact.gif">
			PayVector
		</legend>
		<div class="form-wrapper">
			<div class="form-group">
				<label for="test_mode">Operating Mode</label>
				<select id="test_mode" name="test_mode">
					<option value="true" {if $test_mode === "true"}selected="selected"{/if}>Test Mode</option>
					<option value="false" {if $test_mode === "false"}selected="selected"{/if}>Live Mode</option>
				</select>
			</div>
			<div class="form-group test-credentials">
				<label for="test_merchant_id">Test Merchant ID</label>
				<input id="test_merchant_id" name="test_merchant_id" value="{$test_merchant_id}">
			</div>
			<div class="form-group test-credentials">
				<label for="test_merchant_password">Test Merchant Password</label>
				<input id="test_merchant_password" name="test_merchant_password" value="{$test_merchant_password}" type="password">
			</div>
			<div class="form-group live-credentials">
				<label for="merchant_id">Live Merchant ID</label>
				<input id="merchant_id" name="merchant_id" value="{$merchant_id}">
			</div>
			<div class="form-group live-credentials">
				<label for="merchant_password">Live Merchant Password</label>
				<input id="merchant_password" name="merchant_password" value="{$merchant_password}" type="password">
			</div>
			<div class="form-group">
				<label for="capture_method">Capture Method</label>
				<select id="capture_method" name="capture_method">
					<option value="Direct API" {if $capture_method === "Direct API"}selected="selected"{/if}>Direct/API</option>
					<option value="Hosted Payment Form" {if $capture_method === "Hosted Payment Form"}selected="selected"{/if}>Hosted Payment Form</option>
				</select>
			</div>
			<div class="form-group hpf-options">
				<label for="pre_shared_key">Pre Shared Key</label>
				<input id="pre_shared_key" name="pre_shared_key" value="{$pre_shared_key}">
			</div>
			<div class="form-group hpf-options">
				<label for="hash_method">Hash Method</label>
				<select id="hash_method" name="hash_method">
					<option value="MD5" {if $capture_method === "MD5"}selected="selected"{/if}>MD5</option>
					<option value="HMACMD5" {if $capture_method === "HMACMD5"}selected="selected"{/if}>HMACMD5</option>
					<option value="SHA1" {if $capture_method === "SHA1"}selected="selected"{/if}>SHA1</option>
					<option value="HMACSHA1" {if $capture_method === "HMACSHA1"}selected="selected"{/if}>HMACSHA1</option>
				</select>
			</div>
			<div class="form-group hpf-options">
				<label for="result_delivery_method">Result Delivery Method</label>
				<select id="result_delivery_method" name="result_delivery_method">
					<option value="POST" {if $capture_method === "POST"}selected="selected"{/if}>POST</option>
					<option value="SERVER_PULL" {if $capture_method === "SERVER_PULL"}selected="selected"{/if}>SERVER_PULL</option>
				</select>
			</div>
			<div class="form-group">
				<label for="enable_saved_card">Saved Card Functionality</label>
				<select id="enable_saved_card" name="enable_saved_card">
					<option value="true" {if $enable_saved_card === "true"}selected="selected"{/if}>Enable</option>
					<option value="false" {if $enable_saved_card === "false"}selected="selected"{/if}>Disable</option>
				</select>
			</div>
			<div class="form-group">
				<label for="enable_3dsecure_on_cross_reference">3DSecure on Cross Reference Transactions</label>
				<select id="enable_3dsecure_on_cross_reference" name="enable_saved_card">
					<option value="true" {if $enable_3dsecure_on_cross_reference === "true"}selected="selected"{/if}>Enable</option>
					<option value="false" {if $enable_3dsecure_on_cross_reference === "false"}selected="selected"{/if}>Disable</option>
				</select>
			</div>
			<div style="width: 512px">
				<center><input type="submit" name="update_payvector" value="Update Settings"></center>
			</div>
		</div>
	</fieldset>
</form>

<script>
jQuery(document).ready(function($) {
	var $testMode = $("#test_mode");
	var $captureMethod = $("#capture_method");

	var $testCredentials = $(".test-credentials");
	var $liveCredentials = $(".live-credentials");
	var $hpfOptions = $(".hpf-options");

	//Check on page load which values should be hidden

	if($testMode.val() === "true")
	{
		$liveCredentials.hide();
	}
	else
	{
		$testCredentials.hide();
	}

	if($captureMethod.val() === "Direct API")
	{
		$hpfOptions.hide();
	}

	//Setup bindings

	$testMode.change(function() {
		if($(this).val() === "true")
		{
			$liveCredentials.hide();
			$testCredentials.show();
		}
		else
		{
			$testCredentials.hide();
			$liveCredentials.show();
		}
	});

	$captureMethod.change(function() {
		if($(this).val() === "Direct API")
		{
			$hpfOptions.hide();
		}
		else
		{
			$hpfOptions.show();
		}
	});
});
</script>