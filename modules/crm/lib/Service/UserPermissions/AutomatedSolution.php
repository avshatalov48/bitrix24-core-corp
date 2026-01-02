<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Feature;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ArgumentOutOfRangeException;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->automatedSolution()
 */

class AutomatedSolution
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
		private readonly Admin $admin
	)
	{
	}

	/**
	 * Can user create, update or delete automated solutions and manage their permissions
	 * @return bool
	 */
	public function isAllAutomatedSolutionsAdmin(): bool
	{
		if ($this->admin->isAdmin())
		{
			return true;
		}

		return $this->permissionsManager->hasPermissionLevel(
			\Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList::ENTITY_CODE,
			'CONFIG',
			UserPermissions::PERMISSION_ALL
		);
	}

	/**
	 * Can edit smart processes and permissions in $automatedSolutionId automated solution
	 * @param int $automatedSolutionId
	 * @return bool
	 */
	public function isAutomatedSolutionAdmin(int $automatedSolutionId): bool
	{
		if ($this->isAllAutomatedSolutionsAdmin())
		{
			return true;
		}

		if ($this->canEdit())
		{
			return true;
		}

		return $this->permissionsManager->hasPermissionLevel(
			\Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionConfig::generateEntity($automatedSolutionId),
			UserPermissions::OPERATION_UPDATE,
			UserPermissions::PERMISSION_CONFIG
		);
	}

	/**
	 * Can user create, update or delete automated solutions
	 *
	 * @return bool
	 * @throws ArgumentOutOfRangeException
	 */
	public function canEdit(): bool
	{
		if ($this->admin->isAdmin())
		{
			return true;
		}

		if ($this->isAllAutomatedSolutionsAdmin())
		{
			return true;
		}

		return $this->permissionsManager->hasPermissionLevel(
			\Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList::ENTITY_CODE,
			UserPermissions::OPERATION_UPDATE,
			UserPermissions::PERMISSION_ALL
		);
	}
}
