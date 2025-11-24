
<div class="ps">
			<div class="ps-header">
				<div class="ps-reference">
					<h1>3D Secure Verification</h1>
	<p>You are enrolled for 3D Secure Verification. Your card will not be charged until you verify the transaction. For your security, please fill out the form below to complete your order. Do not click the refresh or back button or this transaction may be interrupted or cancelled</p>
				</div>				
				<br>
<br>
<center>
	<iframe style="width:100%;height:450px;border:1px solid #eee;" scrolling="auto" name="threeDSecureFrame" src="about:blank"></iframe>

	<form method="POST" id="form_auth3d" name="form_auth3d" action="{$FormAction}" {$FormAttributes nofilter}>
    {foreach from=$params key=key item=value}
    <input type="hidden" name="{$key|escape:'html'}" value="{$value|escape:'html'}">
	{/foreach}
	</form>
</center>

{literal}
<script>
    (function() {
        // Wait for DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('form_auth3d');
            if (form) {
                // Small delay ensures iframe is ready
                setTimeout(function() {
                    form.submit();
                }, 300);
            }
        });
    })();
</script>
{/literal}
			</div>
	</div>	




