<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FieldDependenceTable extends Entity\DataManager
{
	const ACTION_ENUM_SHOW = 'show';
	const ACTION_ENUM_HIDE = 'hide';
	const ACTION_ENUM_CHANGE = 'change';


	public static function getTableName()
	{
		return 'b_crm_webform_field_dep';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'FORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'IF_FIELD_CODE' => array(
				'required' => true,
				'data_type' => 'integer',
			),
			'IF_ACTION' => array(
				'data_type' => 'enum',
				'required' => true,
				'validation' => array(__CLASS__, 'validateAction'),
				'values' => static::getActions()
			),
			'IF_VALUE' => array(
				'data_type' => 'string',
			),
			'IF_VALUE_OPERATION' => array(
				'data_type' => 'string',
			),
			'DO_FIELD_CODE' => array(
				'required' => true,
				'data_type' => 'integer',
			),
			'DO_ACTION' => array(
				'data_type' => 'enum',
				'required' => true,
				'validation' => array(__CLASS__, 'validateAction'),
				'values' => static::getActions()
			),
			'DO_VALUE' => array(
				'data_type' => 'string',
			),
		);
	}

	public static function getActions()
	{
		return array(
			static::ACTION_ENUM_SHOW,
			static::ACTION_ENUM_HIDE,
			static::ACTION_ENUM_CHANGE,
		);
	}

	public function validateAction()
	{
		return array(
			new Entity\Validator\Enum(),
		);
	}
}
