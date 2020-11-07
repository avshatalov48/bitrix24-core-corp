<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Main\Entity;

/**
 * Class OrderContactCompanyTable
 * @package Bitrix\Crm\Binding
 */
class OrderContactCompanyTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_order_contact_company';
	}
	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => [
				'primary' => true,
				'data_type' => 'integer'
			],
			'ORDER_ID' => [
				'data_type' => 'integer'
			],
			'ORDER' => [
				'data_type' => '\Bitrix\Sale\Order',
				'reference' => [
					'=this.ORDER_ID' => 'ref.ID'
				]
			],
			'ENTITY_ID' => [
				'data_type' => 'integer'
			],
			'ENTITY_TYPE_ID' => [
				'data_type' => 'integer'
			],
			'SORT' => [
				'data_type' => 'integer',
				'default_value' => 0
			],
			'ROLE_ID' => [
				'data_type' => 'integer',
				'default_value' => 0
			],
			'IS_PRIMARY' => [
				'data_type' => 'boolean',
				'values' => ['N', 'Y'],
				'default_value' => 'N'
			]
		];
	}
}