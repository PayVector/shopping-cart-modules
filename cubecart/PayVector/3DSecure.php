<?php
require('../../../ini.inc.php');
$postData = $_POST ?? [];
$cres = $postData['cres'] ?? '';
$token = $_GET['token'] ?? '';
$step = 2;

$prams = [];
if (!empty($cres)) {
	$prams['threeDSSessionData'] = $postData['threeDSSessionData'] ?? '';
	$prams['cres'] = $postData['cres'] ?? '';	
	$step = 3;	
}
else {
	$prams['threeDSMethodData'] = $postData['threeDSMethodData'] ?? '';
}
$path = str_replace('/modules/gateway/PayVector','',$GLOBALS['rootRel']);
$formurl = $path.'index.php?_a=gateway&gateway=payvector&mode=threeds&step='.$step;
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Launch Payer Authentication Page</title>
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