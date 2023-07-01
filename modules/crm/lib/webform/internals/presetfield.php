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

/**
 * Class PresetFieldTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_PresetField_Query query()
 * @method static EO_PresetField_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_PresetField_Result getById($id)
 * @method static EO_PresetField_Result getList(array $parameters = [])
 * @method static EO_PresetField_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_PresetField createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_PresetField_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_PresetField wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_PresetField_Collection wakeUpCollection($rows)
 */
class PresetFieldTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_field_preset';
	}

	public static function getMap()
	{
		return array(
			'FORM_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'ENTITY_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'FIELD_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'VALUE' => array(
				'data_type' => 'string',
				'required' => true,
			),
		);
	}
}
