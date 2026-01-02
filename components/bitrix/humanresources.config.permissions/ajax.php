<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Internals\Service\Container as InternalsContainer;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!Bitrix\Main\Loader::includeModule('humanresources'))
{
	return;
}

class HumanResourcesConfigPermissionsAjaxController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param list<array{id: string, type: string, title: string, accessRights: array, accessCodes?: array}> $userGroups
	 * @param array|null $deletedUserGroups
	 * @param array|null $parameters
	 *
	 * @return array|null
	 */
	public function savePermissionsAction(array $userGroups = [], ?array $deletedUserGroups = null, ?array $parameters = []): array
	{
		$category = RoleCategory::tryFrom($parameters['category'] ?? '');

		if (!$category)
		{
			return [];
		}

		$accessService = InternalsContainer::getAccessService();
		if (!$accessService->checkAccessToEditPermissions($category))
		{
			$this->addError(new Main\Error('Access denied', 'ACCESS_DENIED'));

			return [];
		}

		try
		{
			$permissionService = Container::getAccessRolePermissionService();
			$permissionService->setCategory($category);

			if (!empty($userGroups))
			{
				$permissionService->saveRolePermissions($userGroups);
				Container::getAccessRoleRelationService()->saveRoleRelation($userGroups);
			}

			if (is_array($deletedUserGroups))
			{
				$this->deleteUserGroups($deletedUserGroups);
			}

			return [
				'USER_GROUPS' => $permissionService->getUserGroups(),
			];
		}
		catch (\Exception)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(
				Loc::getMessage('HUMAN_RESOURCES_CONFIG_PERMISSIONS_DB_ERROR') ?? ''
			);
		}

		return [];
	}

	public function loadAction(?array $parameters): array
	{
		$category = RoleCategory::tryFrom($parameters['category'] ?? '');

		if (!$category)
		{
			return [];
		}

		$accessService = InternalsContainer::getAccessService();
		if (!$accessService->checkAccessToEditPermissions($category))
		{
			$this->addError(new Main\Error('Access denied', 'ACCESS_DENIED'));

			return [];
		}

		$permissionService = Container::getAccessRolePermissionService();
		$permissionService->setCategory($category);

		return [
			'USER_GROUPS' => $permissionService->getUserGroups(),
			'ACCESS_RIGHTS' => $permissionService->getAccessRights(),
		];
	}

	private function deleteUserGroups(array $deletedUserGroups): void
	{
		$deletedUserGroups = array_filter($deletedUserGroups, is_numeric(...));
		$deletedUserGroups = array_map(static fn($groupId) => (int)$groupId, $deletedUserGroups);
		if (empty($deletedUserGroups))
		{
			return;
		}

		Container::getAccessRolePermissionService()->deleteRoles($deletedUserGroups);
	}
}