<?php

namespace Bitrix\Crm\Order\Matcher\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\OrderPropsTable;

Loc::loadMessages(__FILE__);

/**
 * Class PropertyBindingTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * </ul>
 *
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_OrderPropsMatch_Query query()
 * @method static EO_OrderPropsMatch_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_OrderPropsMatch_Result getById($id)
 * @method static EO_OrderPropsMatch_Result getList(array $parameters = [])
 * @method static EO_OrderPropsMatch_Entity getEntity()
 * @method static \Bitrix\Crm\Order\Matcher\Internals\EO_OrderPropsMatch createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Order\Matcher\Internals\EO_OrderPropsMatch_Collection createCollection()
 * @method static \Bitrix\Crm\Order\Matcher\Internals\EO_OrderPropsMatch wakeUpObject($row)
 * @method static \Bitrix\Crm\Order\Matcher\Internals\EO_OrderPropsMatch_Collection wakeUpCollection($rows)
 */

class OrderPropsMatchTable extends Main\Entity\DataManager
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
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_order_props_match';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			],
			'SALE_PROP_ID' => [
				'data_type' => 'integer',
				'required' => true
			],
			'CRM_ENTITY_TYPE' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'CRM_FIELD_TYPE' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'CRM_FIELD_CODE' => [
				'data_type' => 'string',
				'required' => true,
			],
			'SETTINGS' => [
				'data_type' => 'text',
				'serialized' => true
			],
			'SALE_PROPERTY' => [
				'data_type' => '\Bitrix\Sale\Internals\OrderPropsTable',
				'reference' => ['=this.SALE_PROP_ID' => 'ref.ID'],
			],
		];
	}

	public static function getByPropertyId($propertyId)
	{
		return static::getList([
			'filter' => ['SALE_PROP_ID' => $propertyId],
		])->fetch();
	}
}
