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
<div class="hikashop_payvector_end" id="hikashop_payvector_end">
	<span id="hikashop_payvector_end_message" class="hikashop_payvector_end_message">
		<?php echo JText::sprintf('PLEASE_WAIT_BEFORE_REDIRECTION_TO_X', $this->payment_name);?>
	</span>
	<span id="hikashop_payvector_end_spinner" class="hikashop_payvector_end_spinner">
		<img src="<?php echo HIKASHOP_IMAGES.'spinner.gif';?>" onload="setTimeout(function(){var form = document.getElementById('hikashop_payvector_form'); if(form) form.submit();}, 200);" />
	</span>
	<br/>
	<form id="hikashop_payvector_form" name="hikashop_payvector_form" action="<?php echo $this->payment_params->url; ?>" method="POST">
		<?php foreach ($this->payment_params->formFields as $name => $value) { ?>
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars((string)$value); ?>" />
		<?php } ?>
		<?php
			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration("window.hikashop.ready( function() {document.getElementById('hikashop_payvector_form').submit();});");
			hikaInput::get()->set('noform',1);
		?>
		<script type="text/javascript">
			setTimeout(function(){
				var form = document.getElementById('hikashop_payvector_form');
				if (form) form.submit();
			}, 100);
		</script>
	</form>
</div>
