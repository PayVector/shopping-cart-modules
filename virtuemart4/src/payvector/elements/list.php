<?php
/**
 * @package     Joomla.Platform
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Renders a list element
 *
 * @package     Joomla.Platform
 * @subpackage  Parameter
 * @since       11.1
 * @deprecated  Use JForm instead
 */
class JElementList extends JElement
{
	/**
	 * Element type
	 *
	 * @var    string
	 */
	protected $_name = 'List';

	/**
	 * Get the options for the element
	 *
	 * @param   object  The current XML node.
	 *
	 * @return  array
	 * @since   11.1
	 *
	 * @deprecated  12.1
	 */
	protected function _getOptions(&$node)
	{
		$options = array ();
		foreach ($node->children() as $option)
		{
			$val	= $option->attributes('value');
			$text	= $option->data();
			$options[] = JHtml::_('select.option', $val, JText::_($text));
		}
		return $options;
	}

	/**
	 * Fetch the HTML code for the parameter element.
	 *
	 * @param   string   The field name.
	 * @param   mixed    The value of the field.
	 * @param   object   The current XML node.
	 * @param   string   The name of the HTML control.
	 *
	 * @since   11.1
	 *
	 * @deprecated    12.1
	 */
	public function fetchElement($name, $value, &$node, $controlName)
	{
		$control	= $controlName .'['. $name .']';
		$attributes	= ' ';

		if ($v = $node->attributes('size')) {
			$attributes	.= 'size="'.$v.'" ';
		}
		if ($v = $node->attributes('class')) {
			$attributes	.= 'class="'.$v.'" ';
		} else {
			$attributes	.= 'class="inputbox" ';
		}
		if ($m = $node->attributes('multiple'))
		{
			$attributes	.= 'multiple="multiple" ';
			$control		.= '[]';

			// updated to allow array in default setting
			if(!is_array($value))
			if(strpos($value, ',') !== false)
			{
				$value =explode(',', $value);
			}
		}

		//$attributes	.= '';
		$html = '';
		$html .= JHtml::_(
				'select.genericlist',
				$this->_getOptions($node),
				$control,
				array(
					'id' => $controlName.$name,
					'list.attr' => $attributes,
					'list.select' => $value
				)
			);
		return $html;
	}
}
