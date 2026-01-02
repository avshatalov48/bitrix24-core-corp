<?php

namespace Bitrix\Crm\Service\UserPermissions;

use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Loader;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->admin()
 */

class Admin
{
	private const ADMIN_GROUP_ID = 1;

	protected ?bool $isAdmin = null;

	public function __construct(
		private readonly int $userId,
		private readonly PermissionsManager $permissionsManager,
	)
	{
	}

	/**
	 * Is user a portal admin
	 */
	public function isAdmin(): bool
	{
		if ($this->isAdmin !== null)
		{
			return $this->isAdmin;
		}

		$this->isAdmin = false;
		if ($this->userId <= 0)
		{
			return $this->isAdmin; // false
		}

		$currentUser = $this->getCurrentUserObject();
		if ((int)$currentUser->GetID() === $this->userId)
		{
			$this->isAdmin = $currentUser->isAdmin();
			if (!$this->isAdmin)
			{
				$this->isAdmin = in_array(1, $currentUser->GetUserGroupArray(), false);
			}

			return $this->isAdmin;
		}

		try
		{
			if (
				\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
				&& Loader::IncludeModule('bitrix24')
			)
			{
				if (
					class_exists('CBitrix24')
					&& method_exists('CBitrix24', 'IsPortalAdmin')
				)
				{
					// New style check
					$this->isAdmin = \CBitrix24::IsPortalAdmin($this->userId);
				}
			}
			else
			{
				// Check user group 1 ('Portal admins')
				$groups = $currentUser::GetUserGroup($this->userId);
				$this->isAdmin = in_array(self::ADMIN_GROUP_ID, $groups, false);
			}
		}
		catch (\Exception $exception)
		{
		}

		return $this->isAdmin;
	}

	/**
	 * Is crm admin (can write configs)
	 */
	public function isCrmAdmin(): bool
	{
		return $this->permissionsManager->hasPermissionLevel(
			'CONFIG',
			UserPermissions::OPERATION_UPDATE,
			UserPermissions::PERMISSION_CONFIG
		);
	}

	private function getCurrentUserObject(): \CUser
	{
		global $USER;

		return isset($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof \CUser))
			? $USER : new \CUser();
	}
}
