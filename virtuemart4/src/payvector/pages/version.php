<?php
	if(!class_exists(plgVmPaymentPayVector))
		require "../payvector.php";
?>
<table class="paymentGatewaySection">
	<thead>
		<tr>
			<th colspan="2"><h1><?php echo plgVmPaymentPayVector::$adminDisplayName; ?></h1></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Version Number</td>
			<td><?php echo plgVmPaymentPayVector::$versionNumber; ?></td>
		</tr>
		<tr></tr>
	</tbody>
</table>
<br>