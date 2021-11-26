<?php

namespace Bitrix\Crm\EntityForm;

use Bitrix\Ui\EntityForm\Scope;
use CCrmAuthorizationHelper;
use CCrmPerms;

class ScopeAccess extends \Bitrix\Ui\EntityForm\ScopeAccess
{

	/**
	 * @param int $scopeId
	 * @return bool
	 */
	public function canRead(int $scopeId): bool
	{
		return (
			$this->canAdd()
			|| Scope::getInstance()->isHasScope($scopeId)
		);
	}

	/**
	 * @return bool
	 */
	public function canAdd(): bool
	{
		return CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();
	}

	/**
	 * @param int $scopeId
	 * @return bool
	 */
	public function canUpdate(int $scopeId): bool
	{
		$scope = Scope::getInstance()->getById($scopeId);
		return ($this->canAdd() && isset($scope) && $scope['CATEGORY'] === $this->moduleId);
	}

	/**
	 * @param array|int $scopeIds
	 * @return bool
	 */
	public function canDelete($scopeIds): bool
	{
		if (!is_array($scopeIds))
		{
			$scopeIds = [$scopeIds];
		}

		foreach ($scopeIds as $scopeId)
		{
			if (!$this->canUpdate($scopeId))
			{
				return false;
			}
		}

		return true;
	}

	public function isAdmin(): bool
	{
		return \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->isAdmin();
	}
}
