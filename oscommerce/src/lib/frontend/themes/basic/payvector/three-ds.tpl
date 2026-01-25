<div style="text-align:center; padding: 20px;">
    <h1>3D Secure Verification</h1>
	<p>You are enrolled for 3D Secure Verification. Your card will not be charged until you verify the transaction. For your security, please fill out the form below to complete your order. Do not click the refresh or back button or this transaction may be interrupted or cancelled</p>
    
    <iframe name="threeDSecureFrame" width="100%" height="700" frameborder="0"></iframe>
    
    <form id="threeds_form" action="{$acs_url}" method="POST" target="{$target}">
        {foreach $form_params as $key => $value}
            {if $key != 'target'}
                <input type="hidden" name="{$key}" value="{$value}" />
            {/if}
        {/foreach}
    </form>
    
    <script type="text/javascript">
        {literal}
        window.onload = function() {
                var form = document.getElementById('threeds_form');
                if(form) {
                    form.submit();
                }
        };
        {/literal}
    </script>
</div>
