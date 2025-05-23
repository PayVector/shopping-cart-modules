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