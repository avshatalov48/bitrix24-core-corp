<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\DependentVariables;

class UserDepartmentAndOpenedAsAttributes extends UserDepartmentAndOpenedBase
{
	public function getValueForUi(?string $attr, ?array $settings)
	{
		return $this->permissionPreset->convertSingleToMultiValue((string)$attr);
	}

	public function getAttrFromUiValue(array $value): ?string
	{
		return $this->permissionPreset->tryConvertMultiToSingleValue($value);
	}

	public function getSettingsFromUiValue(array $value): ?array
	{
		return null;
	}
}
