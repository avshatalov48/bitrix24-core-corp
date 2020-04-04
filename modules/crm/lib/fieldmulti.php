<?php

namespace Bitrix\Crm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FieldMultiTable extends Entity\DataManager
{

	public static function getTableName()
	{
		return 'b_crm_field_multi';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'ENTITY_ID' => array(
				'data_type' => 'string'
			),
			'ELEMENT_ID' => array(
				'data_type' => 'integer'
			),
			'TYPE_ID' => array(
				'data_type' => 'string'
			),
			'VALUE_TYPE' => array(
				'data_type' => 'string'
			),
			'COMPLEX_ID' => array(
				'data_type' => 'string'
			),
			'VALUE' => array(
				'data_type' => 'string'
			),
		);
	}

}
