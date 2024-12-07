<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\HumanResources\Access\Service;
use Bitrix\Main\Localization\Loc;
use Bitrix\HumanResources\Service\Container;

\Bitrix\Main\Loader::includeModule('humanresources');
class HumanReConfigPermissionsAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function savePermissionsAction(array $userGroups): ?array
	{
		if (empty($userGroups) || !check_bitrix_sessid())
		{
			return null;
		}

		try
		{
			$permissionService = Container::getAccessRolePermissionService();
			$permissionService->saveRolePermissions($userGroups);

			(Container::getAccessRoleRelationService())->saveRoleRelation($userGroups);

			return [
				'USER_GROUPS' => $permissionService->getUserGroups(),
				'ACCESS_RIGHTS' => $permissionService->getAccessRights()
			];
		}
		catch (\Exception)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(
				Loc::getMessage('HUMAN_RESOURCES_CONFIG_PERMISSIONS_DB_ERROR') ?? ''
			);
		}

		return null;
	}

	public function deleteRoleAction(int $roleId): void
	{
		if (!check_bitrix_sessid())
		{
			return;
		}

		try
		{
			(Container::getAccessRolePermissionService())->deleteRole($roleId);
		}
		catch (\Exception)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(
				Loc::getMessage('HUMAN_RESOURCES_CONFIG_ROLE_DELETE_DB_ERROR') ?? ''
			);
		}
	}

	public function loadAction(): array
	{
		$permissionService = Container::getAccessRolePermissionService();

		return [
			'USER_GROUPS' => $permissionService->getUserGroups(),
			'ACCESS_RIGHTS' => $permissionService->getAccessRights()
		];
	}
}