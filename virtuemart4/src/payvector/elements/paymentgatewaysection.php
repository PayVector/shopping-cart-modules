<?php

/* 
 * Product: PayVector Payment Gateway for VirtueMart
 * Version: 1.0.0
 * Release Date: 2014.02.03
 * 
 * Copyright (C) 2014 PayVector <support@payvector.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();
 

/**
 * Renders a title
 */
class JElementPaymentGatewaySection extends JElement
{
    /**
     * Element name
     * @var string
     */
	protected $_name = 'PaymentGatwaySection';
 
    /**
     * Gets the content of a template and returns it as a string
     * @param  string      $name         Name attribute of the element
     * @param  string      $value        Value attribute of the element
     * @param  JXMLElement $node         Element Object
     * @param  string      $control_name Control name of the element
     * @return string                    String containing the results of parsing the template file
     */
	function fetchElement($name, $value, &$node, $control_name)
	{

        if( isset($node->_attributes['start']) )
        {
            //break out of the VirtueMart table
            return "</td></tr></tbody></table>
            <table class='paymentGatewaySection'><thead><tr><th colspan='2'><h1 class='paymentGatewayHeading'>" . $node->data() . "</h1></th></tr></thead><tbody>";
        }
        else if( isset($node->_attributes['end']) )
        {
            //start new VirtueMart table
            return "</tbody></table><br><table width='100%' class='paramlist admintable' cellspacing='1'><tbody><tr><td width='40%' class='paramlist_key'></td><td>";
        }

        
        

        $content = "
        <hr />
        <h1 class='paymentGatewayHeading'>" . $node->data() . "</h1>
        <hr />";

        if(isset( $node->_attributes['col2'] ))
        {
            $content = '</td></tr><tr><td colspan="2" class="paramlist_key paymentGatewayHeadingContainer">' .$content;
        }
        return $content;
	}
}

?>