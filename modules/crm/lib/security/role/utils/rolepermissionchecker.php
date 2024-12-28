<?php

namespace Bitrix\Crm\Security\Role\Utils;



use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;

class RolePermissionChecker
{
	public static function isPermissionEmpty(\Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel $permissionModel): bool
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
			($isFirstLevelPermission && $permissionModel->settings() === $permissionEntity->getMinSettingsValue())
			|| (!$isFirstLevelPermission && $permissionModel->settings() === [Transition::TRANSITION_INHERIT])
		;

		return
			($isEmptyAttribute && $isEmptySettings && $isFirstLevelPermission)
			|| ($isMinAttribute && $isEmptySettings)
			|| ($isMinSettings && $isEmptyAttribute)
		;
	}
}
