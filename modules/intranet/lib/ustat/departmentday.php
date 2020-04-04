<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

use Bitrix\Main\Entity\DataManager;

/**
 * Class description
 * @package bitrix
 * @subpackage intranet
 */
class DepartmentDayTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_intranet_dstat_day';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'DEPT_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'DAY' => array(
				'data_type' => 'date',
				'primary' => true
			),
			'ACTIVE_USERS' => array(
				'data_type' => 'integer'
			),
			'INVOLVEMENT' => array(
				'data_type' => 'integer'
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
