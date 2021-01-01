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

/**
 * Class FieldDepGroupTable
 * @package Bitrix\Crm\WebForm\Internals
 */
class FieldDepGroupTable extends Entity\DataManager
{
	const TYPE_DEF = 0;
	const TYPE_OR = 1;
	const TYPE_AND = 2;

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_webform_field_dep_group';
	}

	/**
	 * Get map.
	 *
	 * @return array
	 */
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
			'TYPE_ID' => array(
				'required' => true,
				'data_type' => 'integer',
				'default_value' => static::TYPE_DEF
			),
		);
	}

	public static function getDepGroupTypes()
	{
		return [
			static::TYPE_DEF => 'Def',
			static::TYPE_OR => 'Or logic',
			static::TYPE_AND => 'And logic',
		];
	}
}
