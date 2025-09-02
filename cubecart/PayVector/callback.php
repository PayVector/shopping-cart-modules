<?php
	require('../../../ini.inc.php'); 
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = str_replace('/modules/gateway/PayVector', '', dirname($script));

    $path = rtrim($protocol.$host.$basePath, '/');

	$formurl = $path.'/index.php?_g=remote&type=gateway&cmd=process&module=PayVector&mode=callback';
	$token = $_GET['token'] ?? '';
	$prams = $_POST ?? [];
?>
<!DOCTYPE html>
<html>
	<head>		
		<script language="javascript">
            function onLoadHandler(){
                document.processform.submit();
            }
		</script>
	</head>
	<body onload="onLoadHandler();" style="margin-left: auto; margin-right: auto;">
		<div name="3DS" style="margin-left: auto; margin-right: auto;">
			<form name="processform" method="post" action="<?php echo $formurl ?>" target="_parent"/>
			<?php
			foreach ($prams as $key => $value) {
    				echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'">'."\n";
				}
			?>			
			<input type="hidden" name="token" value="<?php echo $token ?>" />			
			<noscript>
				<h2>Processing your Payer Authentication Transaction</h2>
				<h3>JavaScript is currently disabled or is not supported by your browser.</h3>
				<h4>Please click Submit to continue the processing of your transaction.</h4>
				<input type="submit" value="Submit">
			</noscript>
			</form>
		</div>
	</body>
</html>