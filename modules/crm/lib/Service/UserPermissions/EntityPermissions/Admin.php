<?php

namespace Bitrix\Crm\Service\UserPermissions\EntityPermissions;

use Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions\AutomatedSolution;
use Bitrix\Crm\Service\UserPermissions\InventoryManagementContractor;
use CCrmOwnerType;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityAdmin()
 */

class Admin
{
	public function __construct(
		private readonly \Bitrix\Crm\Service\UserPermissions\Admin $admin,
		private readonly AutomatedSolution $automatedSolution,
		private readonly InventoryManagementContractor $inventoryManagementContractor,
	)
	{
	}

	/**
	 * Is user an admin of entity
	 *
	 * @param int $entityTypeId
	 * @param int|null $categoryId
	 * @return bool
	 */
	public function isAdminForEntity(int $entityTypeId, ?int $categoryId = null): bool
	{
		if ($this->admin->isAdmin())
		{
			return true;
		}

		if ($categoryId !== null && CategoryRepository::isContractorCategory($entityTypeId, $categoryId))
		{
			return $this->inventoryManagementContractor->canWriteConfig();
		}

		if (CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$automatedSolutionId = Container::getInstance()->getTypeByEntityTypeId($entityTypeId)?->getCustomSectionId();
			if ($automatedSolutionId)
			{
				return $this->automatedSolution->isAutomatedSolutionAdmin($automatedSolutionId);
			}
		}

		return $this->admin->isCrmAdmin();
	}
}
