<div style="text-align: center; width: 100%;">
    <p>Please wait while we process your payment...</p>
    <form action="{$form_url}" method="post" id="three_ds_return_form" target="_parent">
        {foreach $form_params as $key => $value}
            <input type="hidden" name="{$key}" value="{$value}" />
        {/foreach}
    </form>
    <script>
        document.getElementById('three_ds_return_form').submit();
    </script>
</div>
