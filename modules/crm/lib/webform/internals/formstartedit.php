<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Helper;

Loc::loadMessages(__FILE__);

/**
 * Class FormStartEditTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FormStartEdit_Query query()
 * @method static EO_FormStartEdit_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FormStartEdit_Result getById($id)
 * @method static EO_FormStartEdit_Result getList(array $parameters = [])
 * @method static EO_FormStartEdit_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormStartEdit createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormStartEdit_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormStartEdit wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormStartEdit_Collection wakeUpCollection($rows)
 */
class FormStartEditTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_start_edit';
	}

	public static function getMap()
	{
		return array(
			'FORM_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			)
		);
	}
}
