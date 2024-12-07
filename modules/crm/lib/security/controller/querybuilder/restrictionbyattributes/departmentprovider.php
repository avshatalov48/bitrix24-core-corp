<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\RestrictionByAttributes;

use Bitrix\Crm\Integration\HumanResources\DepartmentQueries;
use Bitrix\Crm\Service\Container;
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

		$allUserAttrs = $this->getUserAttributes($userId);

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

	public function getDepartmentsUsers(array $departmentAccessCodes): array
	{
		static $users = [];

		if (empty($departmentAccessCodes))
		{
			return [];
		}

		$cacheKey = md5(implode(',', $departmentAccessCodes));

		if (!isset($users[$cacheKey]))
		{
			$userIds = $this->departmentQueries->queryUserIdsByDepartments($departmentAccessCodes);
			$users[$cacheKey] = array_unique($userIds);
		}

		return $users[$cacheKey];
	}

	private function getUserAttributes(int $userId): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		$attributesProvider = $userPermissions->getAttributesProvider();

		return $attributesProvider->getUserAttributes();
	}
}