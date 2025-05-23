<p>
<?php	
	echo __("You are enrolled for 3D Secure Verification. Your card will not be charged until you verify the transaction. For your security, please fill out the form below
	to complete your order. Do not click the refresh or back button or this transaction may be interrupted or cancelled.", 'wpsc');
?>
</p>
<center>
	<iframe style="width:100%;height:450px;border:1px solid #eee;" scrolling="auto" name="threeDSecureFrame" src="about:blank"></iframe>

	<form method="POST" id="form_auth3d" name="form_auth3d" action="<?php echo $FormAction ?>" <?php echo $FormAttributes ?>>
    <?php
    foreach ($prams as $key => $value) {
        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">' . PHP_EOL;
    }
    ?>
	</form>
</center>

<script>
	var frm = document.getElementById("form_auth3d");
	frm.submit();
</script>