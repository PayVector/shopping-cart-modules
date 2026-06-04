<?php
/**
 * @package	HikaShop for Joomla!
 * @version	6.4
 * @author	PayVector
 * @copyright	(C) 2026. All rights reserved.
 * @license	GNU/GPLv2
 */
defined('_JEXEC') or defined('ABSPATH') or die('Restricted access');

?>
<div class="hikashop_payvector_3ds" id="hikashop_payvector_3ds" style="text-align: center; padding: 30px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 700px; margin: 40px auto; border: 1px solid #e1e8ed;">
	<h1 style="font-size: 24px; color: #2c3e50; margin-bottom: 15px; font-weight: 600;">3D Secure Verification</h1>
	<p style="font-size: 14px; color: #7f8c8d; line-height: 1.6; margin-bottom: 25px;">You are enrolled for 3D Secure Verification. Your card will not be charged until you verify the transaction. For your security, please fill out the form below to complete your order. Do not click the refresh or back button or this transaction may be interrupted or cancelled</p>

	<span id="hikashop_payvector_3ds_message" class="hikashop_payvector_3ds_message" style="display: block; font-size: 14px; color: #3498db; margin-bottom: 10px; font-weight: 500;">
		<?php echo JText::_('Redirecting to 3D Secure verification...'); ?>
	</span>
	<span id="hikashop_payvector_3ds_spinner" class="hikashop_payvector_3ds_spinner" style="display: inline-block; margin-bottom: 15px;">
		<img src="<?php echo HIKASHOP_IMAGES.'spinner.gif';?>" onload="if(typeof start3DS !== 'undefined') start3DS();" />
	</span>
	<br/>
	
	<?php if (!empty($this->payment_params->hasV2)) { ?>
		<form id="method_url_form" name="method_url_form" action="<?php echo htmlspecialchars((string)$this->payment_params->methodUrl); ?>" method="POST" target="threeDSecureFrame">
			<input type="hidden" name="threeDSMethodData" value="<?php echo htmlspecialchars((string)$this->payment_params->ThreeDSMethodData); ?>" />
		</form>
		<iframe id="threeDSecureFrame" name="threeDSecureFrame" style="display:none;"></iframe>
	<?php } elseif (!empty($this->payment_params->isV2Challenge)) { ?>
		<form id="three_ds_form" name="three_ds_form" action="<?php echo htmlspecialchars((string)$this->payment_params->url); ?>" method="POST">
			<input type="hidden" name="creq" value="<?php echo htmlspecialchars((string)$this->payment_params->creq); ?>" />
			<noscript><input type="submit" class="btn btn-primary" value="Click here to proceed." /></noscript>
		</form>
	<?php } else { ?>
		<form id="three_ds_form" name="three_ds_form" action="<?php echo htmlspecialchars((string)$this->payment_params->url); ?>" method="POST">
			<input type="hidden" name="PaReq" value="<?php echo htmlspecialchars((string)$this->payment_params->PaReq); ?>" />
			<input type="hidden" name="MD" value="<?php echo htmlspecialchars((string)$this->payment_params->MD); ?>" />
			<input type="hidden" name="TermUrl" value="<?php echo htmlspecialchars((string)$this->payment_params->TermUrl); ?>" />
			<noscript><input type="submit" class="btn btn-primary" value="Click here to proceed." /></noscript>
		</form>
	<?php } ?>

	<?php
		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration("window.hikashop.ready( function() { if(typeof start3DS !== 'undefined') start3DS(); });");
		hikaInput::get()->set('noform',1);
	?>
	<script type="text/javascript">
		var methodUrlSubmitted = false;
		function submitChallenge() {
			var form = document.getElementById('three_ds_form');
			if (form) form.submit();
		}
		function start3DS() {
			<?php if (!empty($this->payment_params->hasV2)) { ?>
				if (!methodUrlSubmitted) {
					methodUrlSubmitted = true;
					var methodForm = document.getElementById('method_url_form');
					if (methodForm) methodForm.submit();
				}
			<?php } else { ?>
				submitChallenge();
			<?php } ?>
		}
	</script>
</div>
