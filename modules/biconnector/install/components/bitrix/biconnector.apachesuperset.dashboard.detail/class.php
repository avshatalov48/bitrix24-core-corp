<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Configuration\DashboardTariffConfigurator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Integrator\ServiceLocation;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardUrlParameterTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Manager;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule("biconnector");

class ApacheSupersetDashboardDetailComponent extends CBitrixComponent
{
	private SupersetController $supersetController;

	private ?Dashboard $dashboard;

	public function onPrepareComponentParams($arParams)
	{
		$arParams['DASHBOARD_ID'] = (int)($arParams['DASHBOARD_ID'] ?? 0);
		$arParams['CODE'] = $arParams['CODE'] ?? '';
		$arParams['URL_PARAMS'] = $arParams['URL_PARAMS'] ?? [];

		return parent::onPrepareComponentParams($arParams);
	}

	private function getSupersetController(): SupersetController
	{
		if (!isset($this->supersetController))
		{
			$this->supersetController = new SupersetController(Integrator::getInstance());
		}

		return $this->supersetController;
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
			'MARKET_COLLECTION_URL' => MarketDashboardManager::getMarketCollectionUrl(),
			'SUPERSET_SERVICE_LOCATION' => ServiceLocation::getCurrentDatacenterLocationRegion(),
		];

		$embeddedDebugMode = Option::get('biconnector', 'embedded_debug_mode', 'N');
		$this->arResult['EMBEDDED_DEBUG_MODE'] = $embeddedDebugMode === 'Y';
	}

	public function executeComponent()
	{
		$dashboardId = (int)$this->arParams['DASHBOARD_ID'];
		$dashboard = SupersetDashboardTable::getByPrimary($dashboardId)->fetch();
		if (!$dashboard)
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_DETAIL_NOT_FOUND');
			$this->includeComponentTemplate();

			return;
		}

		$accessItem = DashboardAccessItem::createFromArray([
			'ID' => $dashboardId,
			'TYPE' => $dashboard['TYPE'],
			'OWNER_ID' => $dashboard['OWNER_ID'],
		]);

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_VIEW, $accessItem))
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_DETAIL_ACCESS_ERROR');
			$this->includeComponentTemplate();

			return;
		}

		if (
			!empty($dashboard['APP_ID'])
			&& !DashboardTariffConfigurator::isAvailableDashboard($dashboard['APP_ID'])
		)
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BI_CONNECTOR_SUPERSET_DASHBOARD_IS_NOT_TARIFF_AVAILABLE');
			$this->includeComponentTemplate();

			return;
		}

		$this->prepareResult();

		if (SupersetInitializer::isSupersetLoading())
		{
			$dashboard['STATUS'] = SupersetDashboardTable::DASHBOARD_STATUS_LOAD;
			$this->showStartupTemplate($dashboard);

			return;
		}

		if (SupersetInitializer::isSupersetUnavailable())
		{
			$this->showStartupTemplate($dashboard, false);

			return;
		}

		if (!$this->initDashboard()	|| !SupersetInitializer::isSupersetReady())
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_DETAIL_NOT_FOUND');
			$this->includeComponentTemplate();

			return;
		}

		$skeletonStatuses = [SupersetDashboardTable::DASHBOARD_STATUS_LOAD, SupersetDashboardTable::DASHBOARD_STATUS_FAILED];
		if (in_array($this->dashboard->getStatus(), $skeletonStatuses, true))
		{
			$dashboard['STATUS'] = $this->dashboard->getStatus();
			$this->showStartupTemplate($dashboard);

			return;
		}

		$this->dashboard->loadCredentials();
		if (
			(
				$this->dashboard->isAvailableDashboard()
				&& !$this->dashboard->isSupersetDashboardDataLoaded()
			)
			|| !$this->dashboard->isAvailableDashboard()
		)
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_DETAIL_NOT_FOUND');
			$this->includeComponentTemplate();

			return;
		}

		$this->prepareAccessParams();

		$this->prepareEmbeddedCredentials();
		$this->prepareNativeFilters();

		$this->prepareUrlParams();

		$this->includeComponentTemplate();
	}

	private function showStartupTemplate(array $dashboard, bool $supersetAvailable = true): void
	{
		$this->arResult['DASHBOARD_TITLE'] = $dashboard['TITLE'];
		$this->arResult['DASHBOARD_ID'] = $dashboard['ID'];
		$this->arResult['DASHBOARD_STATUS'] = $dashboard['STATUS'];

		$this->arResult['IS_SUPERSET_AVAILABLE'] = $supersetAvailable;

		$this->includeComponentTemplate('startup');
	}

	private function initDashboard(): bool
	{
		$superset = $this->getSupersetController();
		$this->dashboard = $superset->getDashboardRepository()->getById((int)$this->arParams['DASHBOARD_ID'], true);

		if (!$this->dashboard)
		{
			return false;
		}

		if (
			!Manager::isAdmin()
			&& $this->dashboard->getStatus() === SupersetDashboardTable::DASHBOARD_STATUS_DRAFT
			&& $this->dashboard->getOrmObject()->getOwnerId() !== CurrentUser::get()->getId()
		)
		{
			return false;
		}

		return true;
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

	private function prepareUrlParams(): void
	{
		$this->arResult['URL_PARAMS'] = [];
		$this->arResult['PARAMS_COMPATIBLE'] = true;

		$uriVariables = SupersetDashboardUrlParameterTable::getList([
				'filter' => ['=DASHBOARD_ID' => $this->dashboard->getId()],
				'select' => ['ID', 'CODE'],
			])
			->fetchCollection()
		;

		$existedCodes = [];
		foreach ($uriVariables as $uriVariable)
		{
			$existedCodes[$uriVariable->getCode()] = $uriVariable->getId();
		}

		foreach ($existedCodes as $code => $id)
		{
			if (isset($this->arParams['URL_PARAMS'][$code]))
			{
				$this->arResult['URL_PARAMS'][$code] = $this->arParams['URL_PARAMS'][$code];
			}
			else
			{
				$this->arResult['PARAMS_COMPATIBLE'] = false;
			}
		}
	}

	private function prepareAccessParams(): void
	{
		$accessItem = DashboardAccessItem::createFromEntity($this->dashboard);
		$accessController = AccessController::getCurrent();

		$canExport = $accessController->check(ActionDictionary::ACTION_BIC_DASHBOARD_EXPORT, $accessItem);
		$this->arResult['CAN_EXPORT'] = $canExport ? 'Y' : 'N';

		$canEdit = false;
		if (
			$this->dashboard->getType() === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM
			|| $this->dashboard->getType() === SupersetDashboardTable::DASHBOARD_TYPE_MARKET
		)
		{
			$canEdit = $accessController->check(ActionDictionary::ACTION_BIC_DASHBOARD_COPY, $accessItem);
		}
		else if ($this->dashboard->getType() === SupersetDashboardTable::DASHBOARD_TYPE_CUSTOM)
		{
			$canEdit = $accessController->check(ActionDictionary::ACTION_BIC_DASHBOARD_EDIT, $accessItem);
		}
		$this->arResult['CAN_EDIT'] = $canEdit ? 'Y' : 'N';
	}
}
