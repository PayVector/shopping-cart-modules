<?php

namespace Opencart\System\Engine\Extension\Payvector;

use Opencart\System\Library\Extension\Payvector\PayvectorLib;

if (defined('DIR_EXTENSION')) {
	require_once DIR_EXTENSION . 'payvector/system/library/PayvectorLib.php';
} else {
	require_once DIR_SYSTEM . 'library/PayvectorLib.php';
}

/**
 * Class Model
 *
 * @package Opencart\System\Engine\Extension\Payvector
 */
class Model extends \Opencart\System\Engine\Model
{
	use PayvectorLib;

	protected $error = array();

}