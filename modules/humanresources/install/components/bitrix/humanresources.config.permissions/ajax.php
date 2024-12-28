<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Access\Service;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;

if (!Bitrix\Main\Loader::includeModule('humanresources'))
{
	return;
}

class HumanResourcesConfigPermissionsAjaxController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param list<array{id: string, type: string, title: string, accessRights: array, accessCodes?: array}> $userGroups
	 * @return array|null
	 */
	public function savePermissionsAction(array $userGroups): ?array
	{
		if (!\Bitrix\HumanResources\Config\Storage::canUsePermissionConfig())
		{
			return [];
		}

		if (
			empty($userGroups)
			|| !check_bitrix_sessid()
			|| !StructureAccessController::can(CurrentUser::get()->getId(), StructureActionDictionary::ACTION_USERS_ACCESS_EDIT)
		)
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
		if (
			!check_bitrix_sessid()
			|| !StructureAccessController::can(CurrentUser::get()->getId(), StructureActionDictionary::ACTION_USERS_ACCESS_EDIT)
		)
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
		if (!StructureAccessController::can(CurrentUser::get()->getId(), StructureActionDictionary::ACTION_USERS_ACCESS_EDIT))
		{
			return [];
		}
		$permissionService = Container::getAccessRolePermissionService();

		return [
			'USER_GROUPS' => $permissionService->getUserGroups(),
			'ACCESS_RIGHTS' => $permissionService->getAccessRights()
		];
	}
}