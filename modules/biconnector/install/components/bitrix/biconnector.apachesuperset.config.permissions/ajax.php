<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Component\PermissionConfig;
use Bitrix\BIConnector\Access\Service\RolePermissionService;
use Bitrix\BIConnector\Superset\ActionFilter\BIConstructorAccess;
use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;

if (!Bitrix\Main\Loader::includeModule('biconnector'))
{
	return;
}

class ApacheSupersetConfigPermissionsAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new BIConstructorAccess(),
		];
	}

	public function savePermissionsAction(array $userGroups, array $parameters = []): ?array
	{
		if (!$this->checkEditPermissions())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_ACCESS_DENIED'));

			return null;
		}

		if (!$userGroups)
		{
			return null;
		}

		try
		{
			$rolePermissionService = new RolePermissionService();

			$rolePermissionService->saveRolePermissions($userGroups);

			return $this->loadData();
		}
		catch (\Exception)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_DB_ERROR'));
		}

		return null;
	}

	public function deleteRoleAction(int $roleId): ?bool
	{
		if (!$this->checkEditPermissions())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_ACCESS_DENIED'));

			return null;
		}

		try
		{
			(new RolePermissionService())->deleteRole($roleId);
		}
		catch (\Bitrix\Main\DB\SqlQueryException)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_ROLE_DELETE_DB_ERROR'));

			return null;
		}

		return true;
	}

	/**
	 *
	 * @return null | array
	 */
	public function loadAction(): ?array
	{
		if (!$this->checkEditPermissions())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_ACCESS_DENIED'));

			return null;
		}

		return $this->loadData();
	}

	/**
	 * @return array
	 */
	private function loadData(): array
	{
		$configPermissions = new PermissionConfig();

		return [
			'USER_GROUPS' => $configPermissions->getUserGroups(),
			'ACCESS_RIGHTS' => $configPermissions->getAccessRights()
		];
	}

	private function checkEditPermissions(): bool
	{
		if (Loader::includeModule('bitrix24') && !Feature::isFeatureEnabled('bi_constructor_rights'))
		{
			return false;
		}

		return AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_SETTINGS_EDIT_RIGHTS);
	}
}
