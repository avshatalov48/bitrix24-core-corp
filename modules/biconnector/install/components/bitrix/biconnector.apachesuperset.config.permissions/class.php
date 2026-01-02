<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Component\PermissionConfig;
use Bitrix\BIConnector\Access\Permission\PermissionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Main\Loader::requireModule('biconnector');

class ApacheSupersetConfigPermissionsComponent
	extends CBitrixComponent
	implements Main\Errorable
{
	use Main\ErrorableImplementation;

	/**
	 * @inheritDoc
	 */
	public function executeComponent()
	{
		global $APPLICATION;

		/**
		 * @var \CMain $APPLICATION
		 */

		$this->errorCollection = new Main\ErrorCollection();

		$analyticEvent = new AnalyticsEvent('open_editor', 'BI_Builder', 'roles');
		$analyticEvent->setSection('BI_Builder')->setElement('menu');

		if (!$this->checkAccessPermissions())
		{
			$analyticEvent->setStatus('blocked')->send();
			$this->errorCollection->setError(new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_WRONG_PERMISSION')));
			$this->printErrors();

			return;
		}

		$isSetTitle = ($this->arParams['SET_TITLE'] ?? 'Y') === 'Y';
		if ($isSetTitle)
		{
			$APPLICATION->SetTitle(
				Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_ROLE_EDIT_COMP_ACCESS_RIGHTS')
			);
		}

		$analyticEvent->setStatus('success')->send();

		$this->initResult();
		$this->includeComponentTemplate();
	}

	/**
	 * @return void
	 */
	private function initResult(): void
	{
		$this->arResult['ERRORS'] = [];
		$this->arResult['ACTION_URI'] = $this->getPath() . '/ajax.php';
		$this->arResult['NAME'] = Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_ROLE_EDIT_COMP_TEMPLATE_NAME');

		$configPermissions = new PermissionConfig();

		$this->arResult['USER_GROUPS'] = $configPermissions->getUserGroups();
		$this->arResult['ACCESS_RIGHTS'] = $configPermissions->getAccessRights();
		$this->arResult['NEW_GROUP_PERMISSIONS'] = PermissionDictionary::getNewGroupPermissions();
		$this->initDashboardGroups();
	}

	private function initDashboardGroups(): void
	{
		$resultGroups = [];
		$resultDashboards = [];
		$accessibleGroupIds = [];

		$allowedGroups = array_flip(
			AccessController::getCurrent()->getAllowedGroupValue(ActionDictionary::ACTION_BIC_DASHBOARD_VIEW),
		);

		$groups = SupersetDashboardGroupTable::getList([
			'select' => ['ID', 'NAME', 'TYPE', 'DASHBOARDS', 'SCOPE', 'DASHBOARD_SCOPES' => 'DASHBOARDS.SCOPE'],
			'cache' => ['ttl' => 3600],
		]);
		while ($group = $groups->fetchObject())
		{
			$groupScopes = [];
			foreach ($group->getScope() as $scope)
			{
				$groupScopes[] = [
					'code' => $scope->getScopeCode(),
					'name' => $scope->getName(),
				];
			}

			foreach ($group->getDashboards() as $dashboard)
			{
				$dashboardId = $dashboard->getId();
				if (isset($resultDashboards[$dashboardId]))
				{
					continue;
				}

				$resultDashboards[$dashboardId] = [
					'id' => $dashboardId,
					'name' => $dashboard->getTitle(),
					'type' => $dashboard->getType(),
					'createdById' => $dashboard->getCreatedById(),
					'scopes' => [],
				];
				foreach ($dashboard->getScope() as $scope)
				{
					$resultDashboards[$dashboardId]['scopes'][] = [
						'code' => $scope->getScopeCode(),
						'name' => $scope->getName(),
					];
				}
			}

			$groupId = PermissionDictionary::getDashboardGroupPermissionId($group->getId());

			if (isset($allowedGroups[$group->getId()]))
			{
				$accessibleGroupIds[] = $groupId;
			}

			$resultGroups[] = [
				'id' => $groupId,
				'name' => $group->getName(),
				'type' => $group->getType(),
				'dashboardIds' => $group->getDashboards()->getIdList(),
				'scopes' => $groupScopes,
			];
		}

		$this->arResult['DASHBOARD_GROUPS'] = $resultGroups;
		$this->arResult['DASHBOARDS'] = $resultDashboards;
		$this->arResult['USER_DATA'] = [
			'id' => AccessController::getCurrent()->getUser()->getUserId(),
			'isAdmin' => AccessController::getCurrent()->getUser()->isAdmin(),
			'hasAccessToPermission' => AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_SETTINGS_EDIT_RIGHTS),
			'accessibleGroupIds' => $accessibleGroupIds,
		];
	}

	/**
	 * Check can user view and change rights.
	 *
	 * @return bool
	 */
	private function checkAccessPermissions(): bool
	{
		return AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS)
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_SETTINGS_EDIT_RIGHTS)
		;
	}

	/**
	 * Show component errors.
	 *
	 * @return void
	 */
	private function printErrors(): void
	{
		Toolbar::deleteFavoriteStar();
		foreach ($this->errorCollection as $error)
		{
			$this->includeErrorComponent($error->getMessage());
		}
	}

	/**
	 * Include errors component.
	 *
	 * @param string $errorMessage
	 * @param string|null $description
	 *
	 * @return void
	 */
	protected function includeErrorComponent(string $errorMessage, string $description = null): void
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			'bitrix:ui.info.error',
			'',
			[
				'TITLE' => $errorMessage,
				'DESCRIPTION' => $description,
			]
		);
	}
}
