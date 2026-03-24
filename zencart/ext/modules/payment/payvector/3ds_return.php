<?php
$storeRoot = '../../../../';
$form_url = $storeRoot . 'index.php?main_page=payvector_3ds';

$form_params = $_POST;
?>
<div style="text-align: center; width: 100%;">
    <p>Please wait while we process your payment...</p>
    <form action="<?php echo htmlspecialchars($form_url, ENT_QUOTES, 'UTF-8'); ?>" method="post" id="three_ds_return_form" target="_parent">
        <?php foreach ($form_params as $key => $value) { ?>
            <input type="hidden" name="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" />
        <?php } ?>
    </form>
    <script>
        document.getElementById('three_ds_return_form').submit();
    </script>
</div>
