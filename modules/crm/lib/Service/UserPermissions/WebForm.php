<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Security\Role\Manage\Entity\WebFormConfig;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->webForm()
 */

final class WebForm
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
	)
	{
	}

	public function canRead(): bool
	{
		return $this->permissionsManager->hasPermission(
			\Bitrix\Crm\Security\Role\Manage\Entity\WebForm::ENTITY_CODE,
			UserPermissions::OPERATION_READ
		);
	}

	public function canEdit(): bool
	{
		return $this->permissionsManager->hasPermission(
			\Bitrix\Crm\Security\Role\Manage\Entity\WebForm::ENTITY_CODE,
			UserPermissions::OPERATION_UPDATE
		);
	}

	public function canWriteConfig(): bool
	{
		return $this->permissionsManager->hasPermissionLevel(
			WebFormConfig::CODE,
			UserPermissions::OPERATION_UPDATE,
			UserPermissions::PERMISSION_ALL
		);
	}
}
