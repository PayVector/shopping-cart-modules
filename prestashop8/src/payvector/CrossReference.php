<?php

class CrossReference extends ObjectModel
{
	/** @var int ID of the customer that this cross reference relates to */
	public $id_customer;

	/** @var string Cross Reference value returned from the last transaction by this customer */
	public $cross_reference;

	/** @var string Last four digits of the card number last used by the customer (only available if using the Direct/API integration method */
	public $card_last_four;

	/** @var string Type of the last card used by the customer (e.g. VISA Business) */
	public $card_type;

	/** @var string Date/time */
	public $last_updated;

	public static $definition = array(
		'table' => 'payvector_cross_reference',
		'primary' => 'id_cross_reference',
		'fields' => array(
			'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'cross_reference' => array('type' => self::TYPE_STRING),
			'card_last_four' => array('type' => self::TYPE_STRING),
			'card_type' => array('type' => self::TYPE_STRING),
			'last_updated' => array('type' => self::TYPE_STRING)
		)
	);

	public function loadFromCustomerID($id_customer)
	{
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('payvector_cross_reference', 'c');
		$sql->where('c.id_customer = ' . $id_customer);
		$cross_reference_result = Db::getInstance()->executeS($sql);
		if(count($cross_reference_result) > 0)
		{
			$this->hydrate($cross_reference_result[0]);
		}
	}

	/**
	 * @return bool Insertion result
	 */
	public function addOrUpdate()
	{
		if (!ObjectModel::$db)
		{
			ObjectModel::$db = Db::getInstance();
		}

		$sql = new DbQueryCore();
		$sql->select('id_cross_reference');
		$sql->from('payvector_cross_reference', 'c');
		$sql->where('c.id_customer = ' . (int)$this->id_customer);
		if(count(Db::getInstance()->executeS($sql)) > 0)
		{
			$this->update();
		}
		else
		{
			$this->add();
		}
	}
}