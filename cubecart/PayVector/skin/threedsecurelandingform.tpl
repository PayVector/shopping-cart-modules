<h1>3D Secure Verification</h1>
<p>You are enrolled for 3D Secure Verification. Your card will not be charged until you verify the transaction. For your security, please fill out the form below to complete your order. Do not click the refresh or back button or this transaction may be interrupted or cancelled</p>
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

<script>

	document.addEventListener("DOMContentLoaded", function() {    
         document.querySelectorAll('input[type="submit"]').forEach(function(btn) {
            btn.disabled = true;        // disable click
            btn.style.display = "none"; // hide it
        });

		var form = document.getElementById("gateway-transfer");
  	if (form) {
    	form.setAttribute("action", "{$FormAction}");
    	form.setAttribute("target", "threeDSecureFrame"); // or "_self"    	
		form.submit();
	  	}
	});
</script>
