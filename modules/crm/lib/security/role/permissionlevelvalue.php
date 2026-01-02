<?php

namespace Bitrix\Crm\Security\Role;

use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserDepartmentAndOpened;

class PermissionLevelValue
{
	private UserDepartmentAndOpened $permissionPreset;

	public function __construct(
		private readonly string $attribute = UserPermissions::PERMISSION_NONE,
		private array $settings = [],
	)
	{
		$this->permissionPreset = new UserDepartmentAndOpened();
		if (  // compatibility mode
			!empty($attribute)
			&& !in_array(UserDepartmentAndOpened::ALL, $this->settings) // no reason to add something if full permissions
		)
		{
			$settingsFromAttributes = $this->permissionPreset->convertSingleToMultiValue($this->attribute);

			$this->settings = array_unique(
				array_merge($this->settings, $settingsFromAttributes)
			);
		}

		$this->settings = $this->removeEmptySettingsValues($this->settings);

		sort($this->settings);
	}

	public function hasSomePermissions(): bool
	{
		return !empty($this->settings);
	}

	public function isEqualToPermissionAttribute(string $permissionAttribute): bool
	{
		$settingsFromAttributes = $this->permissionPreset->convertSingleToMultiValue($permissionAttribute);
		$settingsFromAttributes = $this->removeEmptySettingsValues($settingsFromAttributes);

		sort($settingsFromAttributes);

		return $settingsFromAttributes === $this->settings;
	}

	private function removeEmptySettingsValues(array $values): array
	{
		$permissiveValues = $this->permissionPreset->getPermissiveSettingsVariantsList();

		return array_unique(array_intersect($permissiveValues, $values));
	}

	public function hasMaxPermissions():bool
	{
		return in_array(UserDepartmentAndOpened::ALL, $this->settings, true);
	}

	public function hasOpenedPermissions(): bool
	{
		return in_array(UserDepartmentAndOpened::OPEN, $this->settings, true);
	}

	public function hasSelfPermissions(): bool
	{
		return in_array(UserDepartmentAndOpened::SELF, $this->settings, true);
	}

	public function hasDepartmentPermissions(): bool
	{
		return in_array(UserDepartmentAndOpened::DEPARTMENT, $this->settings, true);
	}

	public function hasSubDepartmentsPermissions(): bool
	{
		return in_array(UserDepartmentAndOpened::SUBDEPARTMENTS, $this->settings, true);
	}
}
