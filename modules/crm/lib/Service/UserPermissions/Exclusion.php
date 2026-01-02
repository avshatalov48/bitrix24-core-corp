<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->exclusion()
 */

final class Exclusion
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
	)
	{
	}

	public function canReadItems(): bool
	{
		return $this->permissionsManager->hasPermission(
			'EXCLUSION',
			UserPermissions::OPERATION_READ
		);
	}

	public function canEditItems(): bool
	{
		return $this->permissionsManager->hasPermission(
			'EXCLUSION',
			UserPermissions::OPERATION_UPDATE
		);
	}
}
