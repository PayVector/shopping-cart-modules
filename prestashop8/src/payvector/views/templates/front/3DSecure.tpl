
	
	<form method="POST" id="form_auth3d" name="form_auth3d" action="{$FormAction}" {$FormAttributes nofilter}>
    {foreach from=$params key=key item=value}
    <input type="hidden" name="{$key|escape:'html'}" value="{$value|escape:'html'}">
	{/foreach}
	</form>
	</form>

<script>
	var frm = document.getElementById("form_auth3d");
	frm.submit();
</script>