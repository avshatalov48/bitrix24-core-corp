<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\DataManager;

/**
 * Class description
 * @package bitrix
 * @subpackage main
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UserHour_Query query()
 * @method static EO_UserHour_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_UserHour_Result getById($id)
 * @method static EO_UserHour_Result getList(array $parameters = array())
 * @method static EO_UserHour_Entity getEntity()
 * @method static \Bitrix\Intranet\UStat\EO_UserHour createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\UStat\EO_UserHour_Collection createCollection()
 * @method static \Bitrix\Intranet\UStat\EO_UserHour wakeUpObject($row)
 * @method static \Bitrix\Intranet\UStat\EO_UserHour_Collection wakeUpCollection($rows)
 */ 
class UserHourTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_intranet_ustat_hour';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'HOUR' => array(
				'data_type' => 'datetime',
				'primary' => true
			),
			'TOTAL' => array(
				'data_type' => 'integer'
			),
			'SOCNET' => array(
				'data_type' => 'integer'
			),
			'LIKES' => array(
				'data_type' => 'integer'
			),
			'TASKS' => array(
				'data_type' => 'integer'
			),
			'IM' => array(
				'data_type' => 'integer'
			),
			'DISK' => array(
				'data_type' => 'integer'
			),
			'MOBILE' => array(
				'data_type' => 'integer'
			),
			'CRM' => array(
				'data_type' => 'integer'
			)
		);

		return $fieldsMap;
	}

	public static function getSectionNames()
	{
		$names = array();
		$fields = static::getEntity()->getFields();

		foreach ($fields as $field)
		{
			if ($field instanceof Entity\ScalarField && !$field->isPrimary() && $field->getName() !== 'TOTAL')
			{
				$names[] = $field->getName();
			}
		}

		return $names;
	}
}
