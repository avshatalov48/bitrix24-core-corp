<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList;
use Bitrix\Crm\Security\Role\Manage\Entity\Button;
use Bitrix\Crm\Security\Role\Manage\Entity\ButtonConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\ContractorConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\WebForm as WebFormEntity;
use Bitrix\Crm\Security\Role\Manage\Entity\WebFormConfig;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\PermIdentifier;
use Bitrix\Crm\Service\UserPermissions\EntityPermissions\Admin as EntityAdmin;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->permission()
 */
final class Permission
{
	public function __construct(
		private readonly Admin $admin,
		private readonly EntityAdmin $entityAdmin,
		private readonly SiteButton $siteButton,
		private readonly WebForm $webForm,
		private readonly AutomatedSolution $automatedSolution,
		private readonly InventoryManagementContractor $inventoryManagementContractor,
	)
	{
	}

	public function canEdit(PermIdentifier $permission): bool
	{
		$canUpdate = match ($permission->entityCode) {
			Button::ENTITY_CODE,
			ButtonConfig::CODE => $this->siteButton->canWriteConfig(),

			WebFormEntity::ENTITY_CODE,
			WebFormConfig::CODE => $this->webForm->canWriteConfig(),

			AutomatedSolutionList::ENTITY_CODE => $this->automatedSolution->isAllAutomatedSolutionsAdmin(),

			ContractorConfig::CODE => $this->inventoryManagementContractor->canWriteConfig(),

			default => null,
		};

		if ($canUpdate !== null)
		{
			return $canUpdate;
		}

		if (str_starts_with($permission->entityCode, AutomatedSolutionConfig::ENTITY_CODE_PREFIX))
		{
			$automatedSolutionId = AutomatedSolutionConfig::decodeAutomatedSolutionId($permission->entityCode);
			if ($automatedSolutionId === null)
			{
				return false;
			}

			return $this->automatedSolution->isAutomatedSolutionAdmin($automatedSolutionId);
		}

		$categoryIdentifier = PermissionEntityTypeHelper::extractEntityAndCategoryFromPermissionEntityType($permission->entityCode);
		if ($categoryIdentifier === null)
		{
			return $this->admin->isAdmin();
		}

		return $this->entityAdmin->isAdminForEntity($categoryIdentifier->getEntityTypeId(), $categoryIdentifier->getCategoryId());
	}
}
