<?php

namespace Bitrix\Crm\Security\Role\Utils;

use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserDepartmentAndOpened;
use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\DependentVariables\UserDepartmentAndOpenedAsSettings;

class RolePermissionChecker
{
	/**
	 * Returns true if the model value is empty or meaningless.
	 * It doesn't matter whether it exists in DB or not, permission check logic will work the same.
	 *
	 * By coincidence, in CRM permissions empty or no permission is the same as "forbid" permission.
	 * So you may think to use this method to check if the permission is "forbid" or not.
	 * Please don't. Use the 'isPermissionAllowsAnything' method instead.
	 *
	 * @see self::isPermissionAllowsAnything
	 */
	public static function isPermissionEmpty(PermissionModel $permissionModel): bool
	{
		$permissionEntity = RoleManagementModelBuilder::getInstance()->getPermissionByCode(
			$permissionModel->entity(),
			$permissionModel->permissionCode(),
		);
		if (!$permissionEntity) // should not detect as empty to avoid deleting permissions which are temporary disabled (e.g. Order)
		{
			return false;
		}

		$isEmptyAttribute = empty($permissionModel->attribute());
		$isEmptySettings = empty($permissionModel->settings());
		$isFirstLevelPermission = is_null($permissionModel->field()) || $permissionModel->field() === '' || $permissionModel->field() === '-';

		$isMinAttribute = $isFirstLevelPermission && $permissionModel->attribute() === $permissionEntity->getMinAttributeValue();
		$isMinSettings =
			(
				$isFirstLevelPermission
				&& (
					$permissionModel->settings() === $permissionEntity->getMinSettingsValue()
					|| $permissionModel->settings() === [UserDepartmentAndOpened::NONE]
				)
			)
			|| (!$isFirstLevelPermission && $permissionModel->settings() === [UserPermissions::SETTINGS_INHERIT])
		;

		return
			($isEmptyAttribute && $isEmptySettings && $isFirstLevelPermission)
			|| ($isMinAttribute && $isEmptySettings)
			|| ($isMinSettings && $isEmptyAttribute)
		;
	}

	/**
	 * Returns true if this exact model gives some permissions to the user - it's
	 * not empty, not neutral (INHERIT), and not forbidding.
	 */
	public static function isPermissionAllowsAnything(PermissionModel $model): bool
	{
		$permissionEntity = RoleManagementModelBuilder::getInstance()->getPermissionByCode(
			$model->entity(),
			$model->permissionCode(),
		);
		if (!$permissionEntity)
		{
			return false;
		}
		$variants = $permissionEntity->variants();
		if (!$variants)
		{
			return false;
		}

		$allowedValues = $variants->getPermissiveValues();

		$attribute = $model->attribute() ?? UserPermissions::PERMISSION_NONE;
		$settings = $model->settings() ?? [];

		$permissionEntityControlMapper = $permissionEntity->getControlMapper();
		if (
			!empty($attribute)
			&& empty($settings)
			&& $permissionEntityControlMapper instanceof UserDepartmentAndOpenedAsSettings // ugly crunch, sorry
		)
		{
			$settings = $permissionEntityControlMapper->convertAttributeToSettings($attribute);
			$attribute = UserPermissions::PERMISSION_NONE;
		}

		return
			in_array($attribute, $allowedValues, true)
			|| !empty(array_intersect($settings, $allowedValues))
		;
	}
}
