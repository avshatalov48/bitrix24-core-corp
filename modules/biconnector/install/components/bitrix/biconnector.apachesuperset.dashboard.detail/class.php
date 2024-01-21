<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\Integration\Superset\Integrator\SupersetServiceLocation;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule("biconnector");

class ApacheSupersetDashboardDetailComponent extends CBitrixComponent
{
	private ?Dashboard $dashboard;
	private bool $isSupersetUp = true;

	public function onPrepareComponentParams($arParams)
	{
		$arParams['DASHBOARD_ID'] = (int)($arParams['DASHBOARD_ID'] ?? 0);

		$arParams['SOURCE_DASHBOARD_ID'] = (int)($arParams['SOURCE_DASHBOARD_ID'] ?? 0);
		$arParams['CODE'] = $arParams['CODE'] ?? '';

		return parent::onPrepareComponentParams($arParams);
	}

	private function prepareResult(): void
	{
		$this->arResult = [
			'FEATURE_AVAILABLE' => true,
			'DASHBOARD_UUID' => null,
			'GUEST_TOKEN' => null,
			'SUPERSET_DOMAIN' => '',
			'ERROR_MESSAGES' => [],
			'NATIVE_FILTERS' => '',
			'SOURCE_DASHBOARD_DATA' => null,
			'MARKET_COLLECTION_URL' => MarketDashboardManager::getMarketCollectionUrl(),
			'IS_EXPORT_ENABLED' => MarketDashboardManager::getInstance()->isExportEnabled() ? 'Y' : 'N',
			'SUPERSET_SERVICE_LOCATION' => SupersetServiceLocation::getCurrentDatacenterLocationRegion(),
		];
	}

	public function executeComponent()
	{
		$dashboardId = (int)$this->arParams['DASHBOARD_ID'];
		$dashboard = \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable::getByPrimary($dashboardId)->fetch();
		if (!$dashboard)
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_DETAIL_NOT_FOUND');
			$this->includeComponentTemplate();
			return;
		}

		$this->prepareResult();

		if (
			$dashboard['STATUS'] !== \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable::DASHBOARD_STATUS_READY
			|| \Bitrix\BIConnector\Integration\Superset\SupersetInitializer::isSupersetLoad()
			|| !$this->initDashboard()
		)
		{
			$this->showStartupTemplate($dashboard);

			return;
		}

		$this->initSourceDashboard();

		if ($this->dashboard === null)
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_DETAIL_NOT_FOUND');
			$this->includeComponentTemplate();
			return;
		}

		$this->prepareEmbeddedCredentials();
		$this->prepareNativeFilters();

		$this->includeComponentTemplate();
	}

	private function showStartupTemplate(array $dashboard): void
	{
		$this->arResult['DASHBOARD_TITLE'] = $dashboard['TITLE'];
		$this->arResult['DASHBOARD_ID'] = $dashboard['ID'];
		$this->arResult['DASHBOARD_STATUS'] =
			$dashboard['STATUS'] === SupersetDashboardTable::DASHBOARD_STATUS_READY
				? SupersetDashboardTable::DASHBOARD_STATUS_LOAD
				: $dashboard['STATUS']
		;

		$this->includeComponentTemplate('startup');
	}

	private function initDashboard(): bool
	{
		$superset = new SupersetController(ProxyIntegrator::getInstance());
		$this->dashboard = $superset->getDashboardRepository()->getById((int)$this->arParams['DASHBOARD_ID']);
		if (!$this->dashboard?->isSupersetDashboardDataLoaded())
		{
			return false;
		}

		return true;
	}

	private function initSourceDashboard(): void
	{
		if (isset($this->arParams['SOURCE_DASHBOARD_ID']) && $this->arParams['SOURCE_DASHBOARD_ID'] > 0)
		{
			$dashboardId = (int)$this->arParams['SOURCE_DASHBOARD_ID'];
			$superset = new SupersetController(ProxyIntegrator::getInstance());
			$dashboard = $superset->getDashboardRepository()->getById($dashboardId);

			if ($dashboard === null)
			{
				return;
			}

			$this->arResult['SOURCE_DASHBOARD_DATA'] = [
				'title' => $dashboard->getTitle(),
				'link' => "/bi/dashboard/detail/{$dashboard->getId()}/",
			];
		}
	}

	private function prepareEmbeddedCredentials(): void
	{
		$this->arResult['DASHBOARD_TYPE'] = $this->dashboard->getType();
		$this->arResult['DASHBOARD_TITLE'] = $this->dashboard->getTitle();
		$this->arResult['DASHBOARD_UUID'] = $this->dashboard->getEmbeddedCredentials()->uuid;
		$this->arResult['DASHBOARD_ID'] = $this->dashboard->getId();
		$this->arResult['GUEST_TOKEN'] = $this->dashboard->getEmbeddedCredentials()->guestToken;
		$this->arResult['SUPERSET_DOMAIN'] = $this->dashboard->getEmbeddedCredentials()->supersetDomain;
		$this->arResult['DASHBOARD_EDIT_URL'] = $this->dashboard->getEditUrl();
		$this->arResult['DASHBOARD_APP_ID'] = $this->dashboard->getAppId();
	}

	private function prepareNativeFilters(): void
	{
		$this->arResult['NATIVE_FILTERS'] = $this->dashboard->getNativeFilter();
	}
}
