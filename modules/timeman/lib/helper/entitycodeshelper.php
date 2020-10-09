<?php
namespace Bitrix\Timeman\Helper;

class EntityCodesHelper
{
	public static function extractUserIdsFromEntityCodes($entityCodesParams)
	{
		$userIds = [];
		foreach ($entityCodesParams as $entityCode)
		{
			if (preg_match('#U[0-9]+#', $entityCode) === 1)
			{
				$userIds[] = (int)mb_substr($entityCode, 1);
			}
		}
		return $userIds;
	}

	public static function extractDepartmentIdsFromEntityCodes($entityCodesParams)
	{
		$ids = [];
		foreach ($entityCodesParams as $entityCode)
		{
			if (preg_match('#DR[0-9]+#', $entityCode) === 1)
			{
				$ids[] = (int)mb_substr($entityCode, 2);
			}
		}
		return $ids;
	}

	public static function buildDepartmentCode($departmentId)
	{
		return 'DR' . $departmentId;
	}

	public static function buildDepartmentCodes($departmentsIds)
	{
		return array_map(function ($id) {
			return static::buildDepartmentCode($id);
		}, $departmentsIds);
	}

	public static function buildUserCodes($userIds)
	{
		return array_map(function ($id) {
			return static::buildUserCode($id);
		}, $userIds);
	}

	public static function buildUserCode($userId)
	{
		return 'U' . $userId;
	}

	public static function isUser($entityCode)
	{
		$ids = static::extractUserIdsFromEntityCodes([$entityCode]);
		return !empty($ids);
	}

	public static function isDepartment($entityCode)
	{
		$ids = static::extractDepartmentIdsFromEntityCodes([$entityCode]);
		return !empty($ids);
	}

	public static function getUserId($entityCode)
	{
		$ids = static::extractUserIdsFromEntityCodes([$entityCode]);
		return reset($ids);
	}

	public static function getDepartmentId($entityCode)
	{
		$ids = static::extractDepartmentIdsFromEntityCodes([$entityCode]);
		return reset($ids);
	}

	public static function getAllUsersCode()
	{
		return 'UA';
	}

	public static function isAllUsers($entityCode)
	{
		return $entityCode === static::getAllUsersCode();
	}
}