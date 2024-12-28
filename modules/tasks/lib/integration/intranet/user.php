<?
/**
 * Class implements all further interactions with "extranet" module
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Intranet;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Util;

final class User extends \Bitrix\Tasks\Integration\Intranet
{
	/**
	 * Returns userfield code by which users are connected with departments
	 * @return string
	 */
	public static function getDepartmentUFCode()
	{
		return 'UF_DEPARTMENT';
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function isEmail(int $userId): bool
	{
		if (!self::includeModule())
		{
			return false;
		}

		if ($userId <= 0)
		{
			return false;
		}

		return (new \Bitrix\Intranet\User($userId))->isEmail();
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function isIntranet(int $userId): bool
	{
		if (!self::includeModule())
		{
			return false;
		}

		if ($userId <= 0)
		{
			return false;
		}

		return (new \Bitrix\Intranet\User($userId))->isIntranet();
	}

	public static function isCollaber(int $userId): bool
	{
		return \Bitrix\Tasks\Integration\Extranet\User::isCollaber($userId);
	}

	public static function getType(int $userId): string
	{
		if (!self::includeModule())
		{
			return '';
		}

		if ($userId <= 0)
		{
			return '';
		}

		return \Bitrix\Intranet\Util::getUserStatus($userId);
	}

	/**
	 * Checks if a given user is a director (has subordinate users or departments)
	 *
	 * @param int $userId
	 * @return bool
	 */
	public static function isDirector($userId = 0)
	{
		if(!$userId)
		{
			$userId = \Bitrix\Tasks\Util\User::getId();
		}

		if(!$userId)
		{
			return false;
		}

		$subs = Department::getSubordinateIds($userId);

		return !empty($subs);
	}

	public static function getSubordinateSubDepartments($userId = 0, $allowedDepartments = null)
	{
		return static::getSubordinate($userId, $allowedDepartments, true);
	}

	public static function getSubordinate($userId = 0, $allowedDepartments = null, $includeSubDepartments = false, $withFired = false)
	{
		if(!static::includeModule())
		{
			return array();
		}

		$arDepartmentHeads = array();

		$arQueueDepartmentsEmployees = array();	// IDs of departments where we need employees

		// Departments where given user is head

		$arManagedDepartments = \CIntranetUtils::getSubordinateDepartments($userId ? $userId : null, $includeSubDepartments);

		if (is_array($allowedDepartments))
		{
			$arManagedDepartments = array_intersect(
				$arManagedDepartments,
				$allowedDepartments
			);
		}

		if (is_array($arManagedDepartments))
		{
			foreach ($arManagedDepartments as $departmentId)
			{
				$arQueueDepartmentsEmployees[] = $departmentId;

				$result = static::searchImmediateEmployeesInSubDepartments($departmentId);

				$arDepartmentHeads = array_merge(
					$arDepartmentHeads,
					$result['arDepartmentHeads']
				);

				$arQueueDepartmentsEmployees = array_merge(
					$arQueueDepartmentsEmployees,
					$result['arQueueDepartmentsEmployees']
				);
			}
		}

		$arEmployees = $arDepartmentHeads;

		if ( ! empty($arQueueDepartmentsEmployees) )
		{
			$arEmployees = array_merge(
				$arEmployees,
				static::getDepartmentsUsersIds($arQueueDepartmentsEmployees, $withFired)
			);
		}

		if ( ! empty($arEmployees) )
		{
			$arEmployees = array_unique(array_filter($arEmployees));

			// Remove itself
			$curUserIndex = array_search($userId, $arEmployees);
			if ($curUserIndex !== false)
			{
				unset($arEmployees[$curUserIndex]);
			}
		}

		return ($arEmployees);
	}

	public static function getByDepartments(
		array $departmentsIds,
		array $fields = ['ID', 'UF_DEPARTMENT'],
		$withFired = false
	): array
	{
		$departmentsIds = array_unique(array_filter($departmentsIds));

		if (!$departmentsIds)
		{
			return [];
		}

		$select = array_unique(array_merge($fields, ['ID', 'UF_DEPARTMENT']));
		$filter = ['UF_DEPARTMENT' => $departmentsIds];
		if (!$withFired)
		{
			$filter['=ACTIVE'] = 'Y';
		}

		return Util\User::getList([
			'select' => $select,
			'filter' => $filter,
		])->fetchAll();
	}

	private static function searchImmediateEmployeesInSubDepartments($departmentId)
	{
		$arDepartmentHeads           = array();
		$arQueueDepartmentsEmployees = array();	// IDs of departments where we need employees

		$arSubDepartments = \CIntranetUtils::getSubDepartments($departmentId);
		if (is_array($arSubDepartments))
		{
			foreach ($arSubDepartments as $subDepId)
			{
				$headUserId = \CIntranetUtils::GetDepartmentManagerID($subDepId);

				if ($headUserId)
					$arDepartmentHeads[] = $headUserId;
				else
				{
					$arQueueDepartmentsEmployees[] = $subDepId;

					$result = static::searchImmediateEmployeesInSubDepartments($subDepId);

					$arDepartmentHeads = array_merge(
						$arDepartmentHeads,
						$result['arDepartmentHeads']
					);

					$arQueueDepartmentsEmployees = array_merge(
						$arQueueDepartmentsEmployees,
						$result['arQueueDepartmentsEmployees']
					);
				}
			}
		}

		return (array(
			'arDepartmentHeads'           => $arDepartmentHeads,
			'arQueueDepartmentsEmployees' => $arQueueDepartmentsEmployees
		));
	}

	private static function getDepartmentsUsersIds($departmentsIds, $withFired = false)
	{
		$res = static::getByDepartments($departmentsIds, ['ID', 'UF_DEPARTMENT'], $withFired);

		if (!$res)
		{
			return [];
		}

		$list = [];
		foreach ($res as $item)
		{
			$list[] = $item['ID'];
		}

		return $list;
	}
}