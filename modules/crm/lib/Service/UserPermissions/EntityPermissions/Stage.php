<?php

namespace Bitrix\Crm\Service\UserPermissions\EntityPermissions;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Security\EntityPermission\ApproveCustomPermsToExistRole;
use Bitrix\Crm\Security\Role\Manage\Permissions\HideSum;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->stage()
 */

class Stage
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
	)
	{
	}

	/**
	 * Check if user can add items on $stageId.
	 * @param int $entityTypeId
	 * @param int|null $categoryId
	 * @param string $stageId
	 * @return bool
	 */
	public function canAddInStage(int $entityTypeId, ?int $categoryId, string $stageId): bool
	{
		return $this->hasPermissions($entityTypeId, $categoryId, $stageId, UserPermissions::OPERATION_ADD);
	}

	/**
	 * Check if user can update items on $stageId.
	 * @param int $entityTypeId
	 * @param int|null $categoryId
	 * @param string $stageId
	 * @return bool
	 */
	public function canUpdateInStage(int $entityTypeId, ?int $categoryId, string $stageId): bool
	{
		return $this->hasPermissions($entityTypeId, $categoryId, $stageId, UserPermissions::OPERATION_UPDATE);
	}

	/**
	 * Return first stage identifier from $stages of entity with $entityTypeId on $categoryId
	 * where user has permission to do $operation.
	 * If such stage is not found - return null.
	 *
	 * @param int $entityTypeId - entity identifier.
	 * @param ?int $categoryId - category identifier.
	 * @param EO_Status_Collection $stages - collection of stages to search to.
	 * @param string $operation - operation (ADD | UPDATE).
	 * @return string|null
	 */
	public function getFirstAvailableForAddStageId(
		int $entityTypeId,
		?int $categoryId,
		EO_Status_Collection $stages
	): ?string
	{
		foreach ($stages as $stage)
		{
			if ($this->hasPermissions($entityTypeId, $categoryId, $stage->getStatusId(), UserPermissions::OPERATION_ADD))
			{
				return $stage->getStatusId();
			}
		}

		return null;
	}

	private function hasPermissions(int $entityTypeId, ?int $categoryId, string $stageId, string $permissionType): bool
	{
		$entityName = (new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory((int)$categoryId);

		$attributes = [];
		$stageAttribute = \Bitrix\Crm\Service\UserPermissions\Helper\Stage::getStageIdAttributeByEntityTypeId($entityTypeId, $stageId);
		if ($stageAttribute)
		{
			$attributes[] = $stageAttribute;
		}

		return $this->permissionsManager->hasPermissionByEntityAttributes($entityName, $permissionType, $attributes);
	}
}
