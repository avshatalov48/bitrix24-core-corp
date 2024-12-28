<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\DependentVariables\ValuesStrategy;

use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy;
use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\DependentVariables\ValuesStrategy;

final class AttributesAsValues implements ValuesStrategy
{
	public function getMinValue(Permission $permission): array
	{
		return UserRoleAndHierarchy::convertSingleToMultiValue($permission->getMinAttributeValue());
	}

	public function getMaxValue(Permission $permission): array
	{
		return UserRoleAndHierarchy::convertSingleToMultiValue($permission->getMaxAttributeValue());
	}

	public function getValueForUi(Permission $permission, ?string $attr, ?array $settings): array
	{
		return UserRoleAndHierarchy::convertSingleToMultiValue((string)$attr);
	}

	public function getAttrFromUiValue(array $value): ?string
	{
		return UserRoleAndHierarchy::tryConvertMultiToSingleValue($value);
	}

	public function getSettingsFromUiValue(array $value): ?array
	{
		return null;
	}
}
