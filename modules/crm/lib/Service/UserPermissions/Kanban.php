<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Security\EntityPermission\ApproveCustomPermsToExistRole;
use Bitrix\Crm\Security\Role\Manage\Permissions\HideSum;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\Container;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->kanban()
 */

class Kanban
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
		private readonly \Bitrix\Crm\Service\UserPermissions\EntityPermissions\Admin $entityAdmin,
	)
	{
	}

	public function canReadKanbanSumInStage(int $entityTypeId, ?int $categoryId, string $stageId): bool
	{
		if ($this->entityAdmin->isAdminForEntity($entityTypeId))
		{
			return true;
		}
		if ($entityTypeId === \CCrmOwnerType::Invoice) // backward compatibility for old invoices
		{
			return true;
		}
		if ((new ApproveCustomPermsToExistRole())->hasWaitingPermission(new HideSum()))
		{
			return true;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory?->isStagesEnabled())
		{
			return false;
		}

		return $this->hasPermissions($entityTypeId, $categoryId, $stageId);
	}

	private function hasPermissions(int $entityTypeId, ?int $categoryId, string $stageId): bool
	{
		$entityName = (new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory((int)$categoryId);

		$attributes = [];
		$stageAttribute = \Bitrix\Crm\Service\UserPermissions\Helper\Stage::getStageIdAttributeByEntityTypeId($entityTypeId, $stageId);
		if ($stageAttribute)
		{
			$attributes[] = $stageAttribute;
		}

		return $this->permissionsManager->hasPermissionByEntityAttributes($entityName, 'HIDE_SUM', $attributes);
	}
}
