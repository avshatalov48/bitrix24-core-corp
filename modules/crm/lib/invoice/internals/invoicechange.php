<?php

namespace Bitrix\Crm\Invoice\Internals;

use Bitrix\Main;

/**
 * Class InvoiceChangeTable
 * @package Bitrix\Crm\Invoice\Internals;
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