<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @deprecated
 */

use Bitrix\Tasks\Integration\Intranet\User;

class CTaskIntranetTools
{
	/**
	 * if ($arAllowedDepartments === null) => all departments headed by user will be used
	 */
	public static function getImmediateEmployees($userId, $arAllowedDepartments = null)
	{
		if ( ! CModule::IncludeModule('intranet') )
			return (false);

		return User::getSubordinate($userId, $arAllowedDepartments);
	}

	//	public static function getDepartmentsUsers($arDepartmentsIds, $arFields = array('ID'))
	//	{
	//		return User::getByDepartments($arDepartmentsIds, $arFields);
	//	}
}