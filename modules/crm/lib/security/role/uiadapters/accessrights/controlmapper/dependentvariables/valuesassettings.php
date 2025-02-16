<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\DependentVariables;

use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\BaseControlMapper;
use Bitrix\Crm\Security\Role\UIAdapters\RoleEditV2\MultivariablesCompatibilityAdapter;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Access\Permission\PermissionDictionary;

class ValuesAsSettings extends BaseControlMapper
{
	public function getType(): string
	{
		if (!defined('\Bitrix\Main\Access\Permission\PermissionDictionary::TYPE_DEPENDENT_VARIABLES'))
		{
			return 'dependent_variables';
		}

		return PermissionDictionary::TYPE_DEPENDENT_VARIABLES;
	}

	public function getValueForUi(?string $attr, ?array $settings)
	{
		// compatibility with old permission values
		if (in_array($this->permission->code(), MultivariablesCompatibilityAdapter::getPermissionCodes()))
		{
			if (!empty($attr))
			{
				return (new UserRoleAndHierarchy())->convertSingleToMultiValue($attr);
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

	public function getMinValue(): string|array|null
	{
		return $this->permission->getMinSettingsValue();
	}

	public function getMaxValue(): string|array|null
	{
		return $this->permission->getMaxSettingsValue();
	}
}
