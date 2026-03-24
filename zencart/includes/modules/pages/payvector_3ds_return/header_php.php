<?php

$returnUrl = zen_href_link('payvector_3ds', '', 'SSL', false, false, true);

$form_params = $_POST;
?>
<!DOCTYPE html>
<html>
<head><title>Processing...</title></head>
<body style="background-color: #fff;">
<div style="text-align: center; width: 100%; padding: 20px; font-family: sans-serif;">
    <p>Please wait while we process your payment...</p>
    <form action="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post" id="three_ds_return_form" target="_parent">
        <?php foreach ($form_params as $key => $value) { ?>
            <input type="hidden" name="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" />
        <?php } ?>
    </form>
    <script>
        document.getElementById('three_ds_return_form').submit();
    </script>
</div>
</body>
</html>
<?php

exit;
