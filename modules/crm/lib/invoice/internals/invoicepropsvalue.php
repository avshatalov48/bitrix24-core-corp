<?php

namespace Bitrix\Crm\Invoice\Internals;

use	Bitrix\Main\Entity\DataManager,
	Bitrix\Main\Entity\Validator;

/**
 * Class OrderPropsValueTable
 * @package Bitrix\Crm\Invoice\Internals
 */
class InvoicePropsValueTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_crm_invoice_props_value';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'primary' => true,
				'autocomplete' => true,
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'ORDER_ID' => array(
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'ORDER_PROPS_ID' => array(
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'NAME' => array(
				'required' => true,
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getNameValidators'),
			),
			'VALUE' => array(
				'data_type' => 'string',
				'validation'              => array('Bitrix\Sale\Internals\OrderPropsTable', 'getValueValidators'),
				'save_data_modification'  => array('Bitrix\Sale\Internals\OrderPropsTable', 'getValueSaveModifiers'),
				'fetch_data_modification' => array('Bitrix\Sale\Internals\OrderPropsTable', 'getValueFetchModifiers'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getCodeValidators'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
			),

			'PROPERTY' => array(
				'data_type' => 'Bitrix\Sale\Internals\OrderPropsTable',
				'reference' => array('=this.ORDER_PROPS_ID' => 'ref.ID'),
				'join_type' => 'LEFT',
			),
		);
	}

	public static function getNameValidators()
	{
		return array(
			new Validator\Length(1, 255),
		);
	}

	public static function getCodeValidators()
	{
		return array(
			new Validator\Length(null, 50),
		);
	}
}
