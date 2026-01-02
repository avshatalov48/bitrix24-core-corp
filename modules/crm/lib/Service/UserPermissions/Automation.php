<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->automation()
 */

final class Automation
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
		private readonly UserPermissions\EntityPermissions\Admin $entityAdmin,
	)
	{
	}

	/**
	 * Can user edit automation robots
	 * @param int $entityTypeId
	 * @param int|null $categoryId
	 * @return bool
	 */
	public function canEdit(int $entityTypeId, ?int $categoryId = null): bool
	{
		if ($this->entityAdmin->isAdminForEntity($entityTypeId, $categoryId))
		{
			return true;
		}

		$categoryId = $categoryId ?? 0;
		$documentType = (new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory($categoryId);

		return $this->permissionsManager->hasPermissionLevel(
			$documentType,
			'AUTOMATION',
			UserPermissions::PERMISSION_ALL
		);
	}
}
