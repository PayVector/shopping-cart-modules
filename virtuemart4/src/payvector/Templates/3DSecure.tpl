<?php echo nl2br(JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_3D_MSG')) ?>
<br>
<br>
<iframe style="width:100%;height:450px;border:0px solid #eee;" scrolling="auto" name="threeDSecureFrame" src="<?php echo $loading_page?>" ></iframe>


<form method="POST" id="form_auth3d" name="form_auth3d" action="<?php echo $FormAction ?>" <?php echo $FormAttributes ?>>
    <?php
    foreach ($prams as $key => $value) {
        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">' . PHP_EOL;
    }
    ?>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var frm = document.getElementById("form_auth3d");
        if (frm) {
            frm.submit();
        }
    });
</script>