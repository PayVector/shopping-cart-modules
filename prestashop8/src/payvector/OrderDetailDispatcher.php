<?php
/**
 * Created by PhpStorm.
 * User: jyoud
 * Date: 23/09/2014
 * Time: 17:43
 */

class OrderDetailDispatcher extends Dispatcher {
	public function __construct()
	{
		$this->use_routes = (bool)Configuration::get('PS_REWRITING_SETTINGS');

		// Select right front controller
		if (defined('_PS_ADMIN_DIR_'))
		{
			$this->front_controller = self::FC_ADMIN;
			$this->controller_not_found = 'adminnotfound';
		}
		elseif (Tools::getValue('fc') == 'module')
		{
			$this->front_controller = self::FC_MODULE;
			$this->controller_not_found = 'pagenotfound';
		}
		else
		{
			$this->front_controller = self::FC_FRONT;
			$this->controller_not_found = 'pagenotfound';
		}

		$this->setRequestUri();

		// Switch language if needed (only on front)
		if (in_array($this->front_controller, array(self::FC_FRONT, self::FC_MODULE)))
			Tools::switchLanguage();

		if (Language::isMultiLanguageActivated())
			$this->multilang_activated = true;

		$this->loadRoutes();
	}
} 