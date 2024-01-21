<?php

namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\BIConnector\Superset\SystemDashboardManager;
use Bitrix\BIConnector\Superset\UI\UIHelper;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

class MenuItemsHelper
{
	public static function prepareBiMenu(): array
	{
		$menuItems = [];
		$systemDashboards = [];
		$systemDashboardsIterator = SupersetDashboardTable::getList([
			'select' => ['ID', 'TYPE', 'APP_ID'],
			'filter' => ['=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM],
		]);
		while ($row = $systemDashboardsIterator->fetch())
		{
			$systemDashboards[$row['APP_ID']] = $row;
		}

		$item = $systemDashboards[SystemDashboardManager::resolveMarketAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_DEALS)];
		$detailId = $item['ID'] ?? SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_DEALS;
		$menuItems['BI_REPORT_DEALS'] = [
			'ID' => 'BI_REPORT_DEALS',
			'NAME' => SystemDashboardManager::getDashboardTitleByAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_DEALS),
			'URL' => "/bi/dashboard/detail/{$detailId}/?openFrom=menu",
		];

		$item = $systemDashboards[SystemDashboardManager::resolveMarketAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_LEADS)];
		$detailId = $item['ID'] ?? SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_LEADS;
		$menuItems['BI_REPORT_LEADS'] = [
			'ID' => 'BI_REPORT_LEADS',
			'NAME' => SystemDashboardManager::getDashboardTitleByAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_LEADS),
			'URL' => "/bi/dashboard/detail/{$detailId}/?openFrom=menu",
		];

		$item = $systemDashboards[SystemDashboardManager::resolveMarketAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_SALES)];
		$detailId = $item['ID'] ?? SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_SALES;
		$menuItems['BI_REPORT_SALES'] = [
			'ID' => 'BI_REPORT_SALES',
			'NAME' => SystemDashboardManager::getDashboardTitleByAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_SALES),
			'URL' => "/bi/dashboard/detail/{$detailId}/?openFrom=menu",
		];

		$item = $systemDashboards[SystemDashboardManager::resolveMarketAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_SALES_STRUCT)];
		$detailId = $item['ID'] ?? SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_SALES_STRUCT;
		$menuItems['BI_REPORT_SALES_STRUCT'] = [
			'ID' => 'BI_REPORT_SALES_STRUCT',
			'NAME' => SystemDashboardManager::getDashboardTitleByAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_SALES_STRUCT),
			'URL' => "/bi/dashboard/detail/{$detailId}/?openFrom=menu",
		];

		$item = $systemDashboards[SystemDashboardManager::resolveMarketAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_TELEPHONY)];
		$detailId = $item['ID'] ?? SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_TELEPHONY;
		$menuItems['BI_REPORT_TELEPHONY'] = [
			'ID' => 'BI_REPORT_TELEPHONY',
			'NAME' => SystemDashboardManager::getDashboardTitleByAppId(SystemDashboardManager::SYSTEM_DASHBOARD_APP_ID_TELEPHONY),
			'URL' => "/bi/dashboard/detail/{$detailId}/?openFrom=menu",
		];

		$menuItems['BI_REPORT_LIST'] = [
			'ID' => 'BI_REPORT_LIST',
			'NAME' => Loc::getMessage('BICONNECOR_CRM_MENU_BI_REPORT_LIST'),
			'URL' => '/bi/dashboard/?openFrom=menu',
		];

		foreach ($menuItems as $index => $menuItem)
		{
			if (
				Loader::includeModule('bitrix24')
				&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled('bi_constructor')
			)
			{
				$menuItems[$index]['URL'] = null;
				$menuItems[$index]['ON_CLICK'] = 'top.BX.UI.InfoHelper.show("limit_crm_BI_constructor")';
			}
		}

		if (
			Loader::includeModule('rest')
			&& SupersetInitializer::isSupersetActive()
		)
		{
			$menuItems['BI_REPORT_MARKET'] = [
				'ID' => 'BI_REPORT_MARKET',
				'NAME' => Loc::getMessage('BICONNECOR_CRM_MENU_BI_REPORT_MARKET'),
				'ON_CLICK' => UIHelper::getOpenMarketScript(MarketDashboardManager::getMarketCollectionUrl(), 'menu'),
			];
		}

		if (Loader::includeModule('ui'))
		{
			Extension::load([
				'biconnector.apache-superset-feedback-form',
				'biconnector.apache-superset-dashboard-manager',
			]);
			$menuItems['BI_REPORT_ORDER'] = [
				'ID' => 'BI_REPORT_ORDER',
				'NAME' => Loc::getMessage('BICONNECOR_CRM_MENU_BI_REPORT_ORDER'),
				'URL' => null,
				'ON_CLICK' => 'BX.Biconnector.ApacheSupersetFeedbackForm.requestIntegrationFormOpen()',
			];
		}

		return $menuItems;
	}
}
