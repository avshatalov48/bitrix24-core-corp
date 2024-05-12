<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\RestrictionByAttributes;

use Bitrix\Crm\Traits\Singleton;

class DepartmentProvider
{
	use Singleton;

	private DepartmentQueries $departmentQueries;

	public function __construct()
	{
		$this->departmentQueries = DepartmentQueries::getInstance();
	}

	public function getUserDepartmentIDs(int $userId): array
	{
		static $userDepartmentIDs = [];

		if (isset($userDepartmentIDs[$userId]))
		{
			return $userDepartmentIDs[$userId];
		}

		$allUserAttrs = $this->departmentQueries->getUserAttributes($userId);

		$userDepartmentIDs[$userId] = [];

		$intranetAttrs = array_merge(
			$allUserAttrs['INTRANET'] ?? [],
			$allUserAttrs['SUBINTRANET'] ?? []
		);

		foreach ($intranetAttrs as $attr)
		{
			if (AttributesUtils::tryParseDepartment($attr, $value) && $value > 0)
			{
				$userDepartmentIDs[$userId][] = (int)$value;
			}
		}

		return $userDepartmentIDs[$userId];
	}

	public function getDepartmentsUsers(array $departmentIds): array
	{
		static $users = [];

		if (empty($departmentIds))
		{
			return [];
		}

		$cacheKey = md5(implode(',', $departmentIds));

		if (!isset($users[$cacheKey]))
		{
			$userIds = $this->departmentQueries->queryUserIdsByDepartments($departmentIds);
			$cIBlockIds =  $this->departmentQueries->queryCIBlockSectionByIds($departmentIds);
			$userIds = array_merge($userIds, $cIBlockIds);

			$users[$cacheKey] = array_unique($userIds);
		}

		return $users[$cacheKey];
	}
}