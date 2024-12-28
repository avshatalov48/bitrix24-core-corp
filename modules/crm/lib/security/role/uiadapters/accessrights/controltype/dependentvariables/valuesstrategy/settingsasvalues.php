<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\DependentVariables\ValuesStrategy;

use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\DependentVariables\ValuesStrategy;
use Bitrix\Crm\Security\Role\UIAdapters\RoleEditV2\MultivariablesCompatibilityAdapter;
use Bitrix\Crm\Service\UserPermissions;

final class SettingsAsValues implements ValuesStrategy
{
	public function getMinValue(Permission $permission): array
	{
		return $permission->getMinSettingsValue();
	}

	public function getMaxValue(Permission $permission): array
	{
		return $permission->getMaxSettingsValue();
	}

	public function getValueForUi(Permission $permission, ?string $attr, ?array $settings): array|string|null
	{
		if (in_array($permission->code(), MultivariablesCompatibilityAdapter::getPermissionCodes())) // compatibility with old permission values
		{
			if (!empty($attr))
			{
				return \Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy::convertSingleToMultiValue($attr);
			}

			return (array)$settings;
		}

		return (array)$settings;
	}

	public function getAttrFromUiValue(array $value): ?string
	{
		return UserPermissions::PERMISSION_NONE;
	}

	public function getSettingsFromUiValue(array $value): ?array
	{
		return $value;
	}
}
