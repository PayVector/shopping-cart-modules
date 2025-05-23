<?php

namespace Opencart\System\Engine\Extension\Payvector;

use Opencart\System\Library\Extension\Payvector\PayvectorLib;

if (defined('DIR_EXTENSION')) {
	require_once DIR_EXTENSION . 'payvector/system/library/PayvectorLib.php';
} else {
	require_once DIR_SYSTEM . 'library/PayvectorLib.php';
}

/**
 * Class Controller
 *
 * @package Opencart\System\Engine\Extension\Payvector
 */
class Controller extends \Opencart\System\Engine\Controller
{
	use PayvectorLib;

	protected $error = array();

}