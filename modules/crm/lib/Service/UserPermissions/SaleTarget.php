<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Integration\HumanResources\DepartmentQueries;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->saleTarget()
 */

final class SaleTarget
{
	private const PERMISSION_ENTITY = 'SALETARGET';

	public function __construct(
		private readonly int $userId,
		private readonly PermissionsManager $permissionsManager,
	)
	{
	}

	public function canRead(): bool
	{
		return $this->permissionsManager->hasPermission(
			self::PERMISSION_ENTITY,
			UserPermissions::OPERATION_READ
		);
	}

	public function canEdit(): bool
	{
		return $this->permissionsManager->hasPermission(
			self::PERMISSION_ENTITY,
			UserPermissions::OPERATION_UPDATE
		);
	}

	public function canReadAll(): bool
	{
		return $this->permissionsManager->hasPermissionLevel(
			self::PERMISSION_ENTITY,
			UserPermissions::OPERATION_READ,
			UserPermissions::PERMISSION_ALL
		);
	}

	public function filterAvailableToReadUserIds(array $userIds): array
	{
		$permissionLevel = $this->permissionsManager
			->getPermissionLevel(
				self::PERMISSION_ENTITY,
				UserPermissions::OPERATION_READ
			)
		;

		$resultIds = [];

		$hasSelfPermissionLevel = $permissionLevel->hasPermissionLevel(UserPermissions::PERMISSION_SELF);
		$hasDepartmentPermissionLevel = $permissionLevel->hasPermissionLevel(UserPermissions::PERMISSION_DEPARTMENT);
		$hasSubDepartmentPermissionLevel = $permissionLevel->hasPermissionLevel(UserPermissions::PERMISSION_SUBDEPARTMENT);

		if (
			!$hasSelfPermissionLevel
			&& !$hasDepartmentPermissionLevel
			&& !$hasSubDepartmentPermissionLevel
		)
		{
			return $resultIds;
		}

		if ($hasDepartmentPermissionLevel || $hasSubDepartmentPermissionLevel)
		{
			$usersInSameDepartments = DepartmentQueries::getInstance()->getUserColleagues($this->userId, $hasSubDepartmentPermissionLevel);
		}
		$usersInSameDepartments[] = $this->userId;

		foreach ($userIds as $checkUserId)
		{
			$checkUserId = (int)$checkUserId;
			if (in_array($checkUserId, $usersInSameDepartments))
			{
				$resultIds[] = $checkUserId;
			}
		}

		return $resultIds;
	}
}
