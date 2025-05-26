{l s='Please wait while your payment is processed...' mod='payvector'}
<br>
<br>
<form method="POST" id="form_3d_breakout" name="form_auth3d" target="_top" action="{$response_url}">
	<input type="hidden" name="3DSecure_breakout" value="true" />
	<input type="hidden" name="MD" value="{$cross_reference}" />
	<noscript>
		<input type="submit" name="{l s='Javascript is not enabled on your browser, please click to continue' mod='payvector'}">
	</noscript>
</form>

<script>
	var frm = document.getElementById("form_3d_breakout");
	frm.submit();
</script>