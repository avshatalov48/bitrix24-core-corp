<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Security\Role\Manage\Entity\ButtonConfig;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->siteButton()
 */

final class SiteButton
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
	)
	{
	}

	public function canRead(): bool
	{
		return $this->permissionsManager->hasPermission(
			\Bitrix\Crm\Security\Role\Manage\Entity\Button::ENTITY_CODE,
			UserPermissions::OPERATION_READ
		);
	}

	public function canEdit(): bool
	{
		return $this->permissionsManager->hasPermission(
			\Bitrix\Crm\Security\Role\Manage\Entity\Button::ENTITY_CODE,
			UserPermissions::OPERATION_UPDATE
		);
	}

	public function canWriteConfig(): bool
	{
		return $this->permissionsManager->hasPermissionLevel(
			ButtonConfig::CODE,
			UserPermissions::OPERATION_UPDATE,
			UserPermissions::PERMISSION_ALL
		);
	}
}
