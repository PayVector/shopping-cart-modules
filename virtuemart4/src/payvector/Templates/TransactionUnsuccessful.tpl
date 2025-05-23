<?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_RESULT_DECLINED_DESC'); ?>
<br>
<a href="<?php echo $this->redirectURL ?>"><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_CHANGECARD') ?></a>
<table id="vmpaymentResultTable">
	<tr>
		<td><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_PAYMENT_INFO') ?></td>
		<td align='left'><?php echo $this->displayName ?></td>
	</tr>
	<tr>
		<td><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_ORDER_NUMBER') ?></td>
		<td align='left'><?php echo $order['details']['BT']->order_number ?></td>
	</tr>
	<tr>
		<td><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_PAYMENT_AMOUNT') ?></td>
		<td align='left'><?php echo $currency->priceDisplay($order['details']['BT']->order_total) ?></td>
	</tr>
	<tr>
		<td><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_RESULT_DESC') ?></td>
		<td align='left'><?php echo $finalTransactionResult->getUserFriendlyMessage() ?></td>
	</tr>
	<tr>
		<td><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_RESULT_REASON') ?></td>
		<td align='left'><?php echo $finalTransactionResult->getMessage() ?></td>
	</tr>
	<?php
	$errorDetail = $finalTransactionResult->getErrorMessage();
	if(!empty($errorDetail))
    {
    ?>
	<tr>
		<td><?php echo JText::_('VMPAYMENT_' . $this->paymentElementUppercase . '_RESULT_ERROR_DETAIL') ?></td>
		<td align='left'><?php echo $errorDetail ?></td>
	</tr>
	<?php
    }
    ?>
</table>

<?php if($order['details']['BT']->order_number)
{
?>

<br/>
<a href="index.php?option=com_virtuemart&view=orders&task=details&order_number=<?php echo $order['details']['BT']->order_number ?>"><?php echo JText::_('VMPAYMENT_' . $this->
	paymentElementUppercase . '_ORDERLINK') ?></a>
<br/>

<?php
}
?>