<?php

namespace Bitrix\Crm\Invoice\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ShipmentItemTable
 * @package Bitrix\Crm\Invoice\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ShipmentItem_Query query()
 * @method static EO_ShipmentItem_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ShipmentItem_Result getById($id)
 * @method static EO_ShipmentItem_Result getList(array $parameters = [])
 * @method static EO_ShipmentItem_Entity getEntity()
 * @method static \Bitrix\Crm\Invoice\Internals\EO_ShipmentItem createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Invoice\Internals\EO_ShipmentItem_Collection createCollection()
 * @method static \Bitrix\Crm\Invoice\Internals\EO_ShipmentItem wakeUpObject($row)
 * @method static \Bitrix\Crm\Invoice\Internals\EO_ShipmentItem_Collection wakeUpCollection($rows)
 */
class ShipmentItemTable extends Main\Entity\DataManager
{

	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}


	/**
	 * @param $id
	 * @return Main\Entity\DeleteResult
	 * @throws Main\ArgumentException
	 */
	public static function deleteWithItems($id)
	{
		$id = intval($id);
		if ($id <= 0)
			throw new Main\ArgumentNullException("id");

		return ShipmentItemTable::delete($id);
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_invoice_dlv_basket';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		global $DB;

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_ID_FIELD'),
			),
			'ORDER_DELIVERY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_ORDER_DELIVERY_ID_FIELD'),
			),
			'DELIVERY' => array(
				'data_type' => 'Shipment',
				'reference' => array(
					'=this.ORDER_DELIVERY_ID' => 'ref.ID'
				)
			),
			'BASKET_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_BASKET_ID_FIELD'),
			),
			'BASKET' => array(
				'data_type' => '\Bitrix\Crm\Invoice\Internals\Basket',
				'reference' => array(
					'=this.BASKET_ID' => 'ref.ID'
				)
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime'
			),
			'DATE_INSERT_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_INSERT'
				)
			),
			'QUANTITY' => array(
				'data_type' => 'float',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_QUANTITY_FIELD'),
			),
			'RESERVED_QUANTITY' => array(
				'data_type' => 'float',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_RESERVED_QUANTITY_FIELD'),
			),
			'XML_ID' => array(
				'data_type' => 'string'
			),
		);
	}
}