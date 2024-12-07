<?php

namespace Bitrix\BIConnector\Access\Rule;

use Bitrix\BIConnector\Access\Model\UserAccessItem;
use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Main\Loader;

/**
 * @property UserAccessItem $user
 */
class BaseRule extends AbstractRule
{
	/**
	 * Check access permission.
	 * @param AccessibleItem|null $item
	 * @param null $params
	 *
	 * @return bool
	 */
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if ($this->isAlwaysAvailableForAdmin() && $this->isAbleToSkipChecking())
		{
			return true;
		}

		$permissionId = ActionDictionary::getActionPermissionMap()[$params['action']];
		$params['item'] = $item;
		$params['value'] = $item?->getId();
		$params['permissionId'] = $permissionId;

		return $this->check($params);
	}

	public function check(array $params): bool
	{
		$permissionId = $params['permissionId'];
		if (!$permissionId)
		{
			return false;
		}

		return (bool)$this->user->getPermission($permissionId);
	}

	/**
	 * There are some actions that even admin can't do, e.g. delete system dashboard.
	 * For actions like that method should return false.
	 *
	 * @return bool
	 */
	protected function isAlwaysAvailableForAdmin(): bool
	{
		return true;
	}

	/**
	 * @return bool
	 */
	protected function isRightsFeatureEnabled(): bool
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return Feature::isFeatureEnabled('bi_constructor_rights');
	}

	/**
	 * @return bool
	 */
	protected function isAbleToSkipChecking(): bool
	{
		return $this->user->isAdmin() || !$this->isRightsFeatureEnabled();
	}

	/**
	 * @param array $params
	 * @return string | null
	 */
	protected static function getPermissionCode(array $params): ?string
	{
		$permissionCode = ActionDictionary::getDashboardPermissionsMap()[$params['action']];

		if (!$permissionCode)
		{
			return null;
		}

		return (string)$permissionCode;
	}

	public function getPermissionValue($params): ?int
	{
		if ($this->isAlwaysAvailableForAdmin() && $this->isAbleToSkipChecking())
		{
			return 1;
		}

		$permissionCode = static::getPermissionCode($params);

		if (!$permissionCode)
		{
			return null;
		}

		return $this->user->getPermission($permissionCode);
	}
}
