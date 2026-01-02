<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Component\PermissionConfig;
use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Access\Service\DashboardGroupService;
use Bitrix\BIConnector\Access\Service\RolePermissionService;
use Bitrix\BIConnector\Analytics\AnalyticsManager;
use Bitrix\BIConnector\Superset\ActionFilter\BIConstructorAccess;
use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

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

	/**
	 * @param array[] $userGroups
	 * @param string[] $deletedUserGroups
	 * @param array $parameters
	 * @param array $accessRights For groups like G1 need to save dashboards and scopes.
	 * @param string[] $deletedAccessRights Deleted dashboard groups
	 *
	 * @return null|array{'USER_GROUPS' => array[]}
	 */
	public function savePermissionsAction(
		array $userGroups = [],
		array $deletedUserGroups = [],
		array $parameters = [],
		array $accessRights = [],
		array $deletedAccessRights = []
	): ?array
	{
		if (!$this->checkEditPermissions())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_ACCESS_DENIED'));

			return null;
		}

		if (!empty($deletedUserGroups))
		{
			$deleteResult = $this->deleteRoles($deletedUserGroups);
			if (!$deleteResult->isSuccess())
			{
				$this->errorCollection[] = $deleteResult->getError();

				return null;
			}
		}

		$deletedGroupPermissionIdList = array_filter($deletedAccessRights, function ($deletedAccessRight) {
			return PermissionDictionary::isDashboardGroupPermission($deletedAccessRight);
		});
		if (!empty($deletedGroupPermissionIdList))
		{
			$groupIdList = [];
			foreach ($deletedGroupPermissionIdList as $groupId)
			{
				if (str_contains($groupId, 'new'))
				{
					continue;
				}

				$groupIdList[] = PermissionDictionary::getDashboardGroupIdFromPermission($groupId);
			}
			$deleteResult = DashboardGroupService::deleteGroupList($groupIdList);
			if (!$deleteResult->isSuccess())
			{
				$this->errorCollection[] = $deleteResult->getError();

				return null;
			}
		}

		$savePermissionsResult = (new RolePermissionService())->saveRolePermissions($userGroups, $accessRights);
		if (!$savePermissionsResult->isSuccess())
		{
			$this->errorCollection[] = $savePermissionsResult->getError();

			return null;
		}

		AnalyticsManager::sendSavePermissionsAnalytics(AnalyticsManager::GROUP_PERMISSION_SECTION);

		return $this->loadData();
	}

	/**
	 * @param string[] $deletedRoles
	 *
	 * @return Result
	 */
	public function deleteRoles(array $deletedRoles): Result
	{
		$result = new Result();
		foreach ($deletedRoles as $deletedRole)
		{
			try
			{
				(new RolePermissionService())->deleteRole((int)$deletedRole);
			}
			catch (\Bitrix\Main\DB\SqlQueryException)
			{
				$result->addError(new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_ROLE_DELETE_DB_ERROR')));

				return $result;
			}
		}

		return $result;
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
			'ACCESS_RIGHTS' => $configPermissions->getAccessRights(),
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
