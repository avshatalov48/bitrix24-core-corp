<?php

namespace Bitrix\Crm\Invoice\Internals;

use Bitrix\Main;

/**
 * Class InvoiceChangeTable
 * @package Bitrix\Crm\Invoice\Internals;
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_InvoiceChange_Query query()
 * @method static EO_InvoiceChange_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_InvoiceChange_Result getById($id)
 * @method static EO_InvoiceChange_Result getList(array $parameters = [])
 * @method static EO_InvoiceChange_Entity getEntity()
 * @method static \Bitrix\Crm\Invoice\Internals\EO_InvoiceChange createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Invoice\Internals\EO_InvoiceChange_Collection createCollection()
 * @method static \Bitrix\Crm\Invoice\Internals\EO_InvoiceChange wakeUpObject($row)
 * @method static \Bitrix\Crm\Invoice\Internals\EO_InvoiceChange_Collection wakeUpCollection($rows)
 */
class InvoiceChangeTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_invoice_change';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'ORDER_ID' => array(
				'data_type' => 'integer',
				'required'   => true
			),
			'TYPE' => array(
				'data_type' => 'string',
				'required'   => true
			),
			'DATA'  => array(
				'data_type' => 'string'
			),
			'DATE_CREATE'  => array(
				'data_type' => 'datetime',
				'default_value' => new Main\Type\DateTime(),
				'required'   => true
			),
			'DATE_MODIFY'  => array(
				'data_type' => 'datetime',
				'default_value' => new Main\Type\DateTime(),
				'required'   => true
			),
			'USER_ID'  => array(
				'data_type' => 'integer',
			),
			'ENTITY'  => array(
				'data_type' => 'string'
			),
			'ENTITY_ID'  => array(
				'data_type' => 'integer'
			),
		);
	}
}