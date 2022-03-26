<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

use Bitrix\Main\Entity\DataManager;

/**
 * Class description
 * @package bitrix
 * @subpackage intranet
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UserDay_Query query()
 * @method static EO_UserDay_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_UserDay_Result getById($id)
 * @method static EO_UserDay_Result getList(array $parameters = array())
 * @method static EO_UserDay_Entity getEntity()
 * @method static \Bitrix\Intranet\UStat\EO_UserDay createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\UStat\EO_UserDay_Collection createCollection()
 * @method static \Bitrix\Intranet\UStat\EO_UserDay wakeUpObject($row)
 * @method static \Bitrix\Intranet\UStat\EO_UserDay_Collection wakeUpCollection($rows)
 */ 
class UserDayTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_intranet_ustat_day';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'DAY' => array(
				'data_type' => 'date',
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
}
