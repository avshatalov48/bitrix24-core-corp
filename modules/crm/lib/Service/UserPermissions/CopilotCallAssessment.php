<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->copilotCallAssessment()
 */

final class CopilotCallAssessment
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
		private readonly Admin $admin,
	)
	{
	}

	public function canRead(): bool
	{
		if ($this->admin->isCrmAdmin())
		{
			return true;
		}

		return $this->permissionsManager->hasPermissionLevel(
			'CCA',
			UserPermissions::OPERATION_READ,
			UserPermissions::PERMISSION_ALL
		);
	}

	public function canEdit(): bool
	{
		if ($this->admin->isCrmAdmin())
		{
			return true;
		}

		return $this->permissionsManager->hasPermissionLevel(
			'CCA',
			UserPermissions::OPERATION_UPDATE,
			UserPermissions::PERMISSION_ALL
		);
	}
}
