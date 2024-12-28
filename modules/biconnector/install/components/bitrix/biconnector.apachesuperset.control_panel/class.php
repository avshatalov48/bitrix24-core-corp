<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class ApacheSupersetControlPanel extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->prepareMenuItems();

		$this->includeComponentTemplate();
	}

	private function prepareMenuItems(): void
	{
		\Bitrix\Main\UI\Extension::load('biconnector.apache-superset-market-manager');
		$isMarketExists = Loader::includeModule('market') ? 'true' : 'false';
		$marketUrl = CUtil::JSEscape(MarketDashboardManager::getMarketCollectionUrl());

		$menuItems = [
			...$this->getDashboardsForTopMenu(),
			[
				'ID' => 'MARKET',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_MARKET'),
				'ON_CLICK' => "BX.BIConnector.ApacheSupersetMarketManager.openMarket({$isMarketExists}, '{$marketUrl}', 'menu')",
				'IS_DISABLED' => false,
			],
			[
				'ID' => 'ORDER_DASHBOARD',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_ORDER'),
				'ON_CLICK' => 'BX.Biconnector.ApacheSupersetFeedbackForm.requestIntegrationFormOpen()',
				'IS_DISABLED' => false,
			],
			[
				'ID' => 'FEEDBACK',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_FEEDBACK'),
				'ON_CLICK' => 'BX.Biconnector.ApacheSupersetFeedbackForm.feedbackFormOpen()',
				'IS_DISABLED' => false,
			],
		];

		if (!Feature::isExternalEntitiesEnabled())
		{
			$menuItems[] = [
				'ID' => 'BI_ANALYTICS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_ANALYTICS'),
				'IS_LOCKED' => true,
				'ON_CLICK' => <<<JS
						top.BX.UI.InfoHelper.show('limit_BI_analyst_workplace');
					JS,
			];
		}
		elseif (AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG))
		{
			$menuItems[] = [
				'ID' => 'BI_ANALYTICS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_ANALYTICS'),
				'ON_CLICK' => 'BX.BIConnector.DashboardManager.openDatasetListSlider()',
				'IS_DISABLED' => false,
			];
		}

		$settingsItems = [];
		if (AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_SETTINGS_ACCESS))
		{
			$settingsItems[] = [
				'ID' => 'COMMON_SETTINGS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_COMMON_SETTINGS'),
				'ON_CLICK' => 'BX.BIConnector.DashboardManager.openSettingsSlider()',
			];
		}

		if (AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_SETTINGS_EDIT_RIGHTS))
		{
			$menuTitle = Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_RIGHTS_SETTINGS');
			if (!Feature::isBiBuilderRightsEnabled())
			{
				$settingsItems[] = [
					'ID' => 'RIGHTS_SETTINGS',
					'TEXT' => $menuTitle,
					'IS_LOCKED' => true,
					'ON_CLICK' => <<<JS
						BX.BIConnector.ApacheSupersetAnalytics.sendAnalytics('roles', 'open_editor', {
							c_element: 'menu',
							status: 'low_tariff',
						});
						top.BX.UI.InfoHelper.show('limit_crm_BI_constructor_access_permissions');
					JS,
				];
			}
			else
			{
				$settingsItems[] = [
					'ID' => 'RIGHTS_SETTINGS',
					'TEXT' => $menuTitle,
					'ON_CLICK' => "BX.SidePanel.Instance.open('" . \CUtil::JSEscape('/bi/settings/permissions/') . "')",
				];
			}
		}

		if ($settingsItems)
		{
			$menuItems[] = [
				'ID' => 'SETTINGS',
				'TEXT' => Loc::getMessage('BICONNECTOR_CONTROL_PANEL_MENU_ITEM_SETTINGS'),
				'ON_CLICK' => '',
				'ITEMS' => $settingsItems,
			];
		}

		$this->arResult['MENU_ITEMS'] = $menuItems;
	}

	private function getDashboardsForTopMenu(): array
	{
		$userId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		if (!$userId)
		{
			return [];
		}

		$result = [];
		$topMenuDashboards = CUserOptions::getOption('biconnector', 'top_menu_dashboards', [], $userId);
		$dashboardCollection = SupersetDashboardTable::getList([
			'filter' => [
				'=ID' => $topMenuDashboards,
			],
		])->fetchCollection();

		// When we query dashboards with ids [15, 10], ORM returns them in order [10, 15]. We need to keep initial order.
		$sortedDashboards = [];
		foreach ($topMenuDashboards as $topMenuDashboardId)
		{
			$dashboard = $dashboardCollection->getByPrimary($topMenuDashboardId);
			if ($dashboard)
			{
				$sortedDashboards[] = $dashboard;
			}
		}

		foreach ($sortedDashboards as $dashboard)
		{
			$result[] = [
				'ID' => "DASHBOARD_{$dashboard->getId()}",
				'TEXT' => $dashboard->getTitle(),
				'URL' => "/bi/dashboard/detail/{$dashboard->getId()}/?openFrom=menu"
			];
		}

		return $result;
	}
}