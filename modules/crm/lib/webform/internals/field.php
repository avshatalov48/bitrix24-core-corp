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
use Bitrix\Crm\WebForm\Helper;

Loc::loadMessages(__FILE__);

class FieldTable extends Entity\DataManager
{
	const TYPE_ENUM_SECTION = 'section';
	const TYPE_ENUM_PAGE = 'page';

	const TYPE_ENUM_EMAIL = 'email';
	const TYPE_ENUM_PHONE = 'phone';
	const TYPE_ENUM_INT = 'integer';
	const TYPE_ENUM_FLOAT = 'double';
	const TYPE_ENUM_STRING = 'string';
	const TYPE_ENUM_TYPED_STRING = 'typed_string';
	const TYPE_ENUM_LIST = 'list';
	const TYPE_ENUM_CHECKBOX = 'checkbox';
	const TYPE_ENUM_RADIO = 'radio';
	const TYPE_ENUM_TEXT = 'text';
	const TYPE_ENUM_FILE = 'file';
	const TYPE_ENUM_DATE = 'date';
	const TYPE_ENUM_DATETIME = 'datetime';
	const TYPE_ENUM_PRODUCT = 'product';
	const TYPE_ENUM_BOOL = 'boolean';
	const TYPE_ENUM_HR = 'hr';
	const TYPE_ENUM_BR = 'br';
	const TYPE_ENUM_RESOURCEBOOKING = 'resourcebooking';

	public static function getTableName()
	{
		return 'b_crm_webform_field';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TYPE' => array(
				//'data_type' => 'enum',
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE'),
				//'default_value' => static::ENUM_TEMPLATE_BOX,
				//'values' => array_keys(self::getTypeList())
			),
			'CODE' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'CAPTION' => array(
				'data_type' => 'string',
			),
			'ITEMS' => array(
				'data_type' => 'text',
				'serialized' => true
			),
			'FORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),

			'SORT' => array(
				'data_type' => 'integer',
				'default_value' => 100,
				'required' => true,
			),
			'REQUIRED' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => true,
				'values' => array('N','Y')
			),
			'MULTIPLE' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => true,
				'values' => array('N','Y')
			),
			'PLACEHOLDER' => array(
				'data_type' => 'string',
			),
			'VALUE_TYPE' => array(
				'data_type' => 'string',
			),
			'VALUE' => array(
				'data_type' => 'string',
			),
			'SETTINGS_DATA' => array(
				'data_type' => 'text',
				'serialized' => true
			)
		);
	}

	public static function getTypeList()
	{
		return array(
			self::TYPE_ENUM_SECTION => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_SECTION'),
			self::TYPE_ENUM_PAGE => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_SECTION'),
			self::TYPE_ENUM_EMAIL => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_EMAIL'),
			self::TYPE_ENUM_INT => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_INT1'),
			self::TYPE_ENUM_FLOAT => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_FLOAT'),
			self::TYPE_ENUM_PHONE => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_PHONE'),
			self::TYPE_ENUM_LIST => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_LIST'),
			self::TYPE_ENUM_DATE => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_DATE'),
			self::TYPE_ENUM_DATETIME => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_DATETIME'),
			self::TYPE_ENUM_CHECKBOX => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_CHECKBOX'),
			self::TYPE_ENUM_RADIO => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_RADIO'),
			self::TYPE_ENUM_TEXT => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_TEXT'),
			self::TYPE_ENUM_FILE => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_FILE'),
			self::TYPE_ENUM_HR => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_HR'),
			self::TYPE_ENUM_BR => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_BR'),
			self::TYPE_ENUM_STRING => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_STRING'),
			self::TYPE_ENUM_TYPED_STRING => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_TYPED_STRING'),
			self::TYPE_ENUM_PRODUCT => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_PRODUCT'),
			self::TYPE_ENUM_RESOURCEBOOKING => Loc::getMessage('CRM_WEBFORM_FIELD_TYPE_RESOURCEBOOKING')
		);
	}

	public static function onBeforeAdd(Entity\Event $event)
	{
		$fields = $event->getParameter('fields');
		$result = new Entity\EventResult();

		if(!isset($fields['ITEMS']) || !is_array($fields['ITEMS']))
		{
			$result->modifyFields(array('ITEMS' => array()));
		}

		return $result;
	}

	public static function onBeforeUpdate(Entity\Event $event)
	{
		$fields = $event->getParameter('fields');
		$result = new Entity\EventResult();

		if(!isset($fields['ITEMS']) || !is_array($fields['ITEMS']))
		{
			$result->modifyFields(array('ITEMS' => array()));
		}

		return $result;
	}

	public static function isUiFieldType($type)
	{
		$uiTypes = [self::TYPE_ENUM_BR, self::TYPE_ENUM_HR, self::TYPE_ENUM_SECTION, self::TYPE_ENUM_PAGE];

		return in_array($type, $uiTypes);
	}
}
