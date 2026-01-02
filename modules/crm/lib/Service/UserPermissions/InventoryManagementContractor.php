<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Security\Role\Manage\Entity\ContractorConfig;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->inventoryManagementContractor()
 */
final class InventoryManagementContractor
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
	)
	{
	}

	public function canWriteConfig(): bool
	{
		return $this->permissionsManager->hasPermissionLevel(
			ContractorConfig::CODE,
			UserPermissions::OPERATION_UPDATE,
			UserPermissions::PERMISSION_ALL,
		);
	}
}
