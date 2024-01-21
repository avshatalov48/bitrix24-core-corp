<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Grid\DashboardGrid;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\BIConnector\Superset\UI\UIHelper;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons;
use Bitrix\UI\Buttons\SettingsButton;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons\JsCode;

class ApacheSupersetDashboardListComponent extends CBitrixComponent
{
	private const GRID_ID = 'biconnector_superset_dashboard_grid';
	private DashboardGrid $grid;

	public function onPrepareComponentParams($arParams)
	{
		$arParams['ID'] = (int)($arParams['ID'] ?? 0);
		$arParams['CODE'] = $arParams['CODE'] ?? '';

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if (!SupersetInitializer::isSupersetActive())
		{
			SupersetInitializer::createSuperset();
		}
		else
		{
			$manager = \Bitrix\BIConnector\Superset\MarketDashboardManager::getInstance();
			$manager->updateApplications();
		}

		$this->init();
		$this->grid->processRequest();
		$this->loadRows();
		$this->arResult['GRID'] = $this->grid;

		$this->initCreateButton();
		$this->initToolbar();
		$this->includeComponentTemplate();
	}

	private function init(): void
	{
		$settings = new Main\Grid\Settings([
			'ID' => self::GRID_ID,
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'SHOW_TOTAL_COUNTER' => true,
			'EDITABLE' => false,
		]);

		$grid = new DashboardGrid($settings);
		$this->grid = $grid;
		if (empty($this->grid->getOptions()->getSorting()['sort']))
		{
			$this->grid->getOptions()->SetSorting('ID', 'desc');
		}

		$totalCount = SupersetDashboardTable::getCount();
		$grid->initPagination($totalCount);
	}

	private function loadRows(): void
	{
		$rows = $this->getSupersetRows($this->grid->getOrmParams());
		$this->grid->setRawRows($rows);
	}

	private function initCreateButton(): void
	{
		$openMarketScript = UIHelper::getOpenMarketScript(
			MarketDashboardManager::getMarketCollectionUrl(),
			'grid'
		);
		$splitButton = new Buttons\Split\CreateButton();

		$mainButton = $splitButton->getMainButton();
		$mainButton->getAttributeCollection()['onclick'] = $openMarketScript;

		$splitButton->setMenu([
			'items' => [
				[
					'text' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_LIST_MENU_ITEM_CREATE_DASHBOARD'),
					'onclick' => new JsCode($openMarketScript),
				],
				[
					'text' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_LIST_MENU_ITEM_NEW_DASHBOARD'),
					'onclick' => new Bitrix\UI\Buttons\JsCode(
						'this.close(); BX.BIConnector.SupersetDashboardGridManager.Instance.createEmptyDashboard()'
					),
				],
			],
			'closeByEsc' => true,
			'angle' => true,
			'offsetLeft' => 115,
			'autoHide' => true,
		]);

		Toolbar::addButton($splitButton, ButtonLocation::AFTER_TITLE);
	}

	private function initToolbar(): void
	{
		$settingButton = new SettingsButton([
			'click' => new JsCode(
				'BX.BIConnector.DashboardManager.openSettingPeriodSlider()'
			),
		]);

		Toolbar::addButton($settingButton);
	}

	/**
	 * @param array $ormParams
	 * @return Dashboard[]
	 */
	private function getSupersetRows(array $ormParams): array
	{
		$superset = new SupersetController(ProxyIntegrator::getInstance());
		$dashboardList = $superset->getDashboardRepository()->getList($ormParams);
		if (!$dashboardList)
		{
			if ($ormParams['offset'] !== 0)
			{
				$ormParams['offset'] = 0;
				$this->grid->getPagination()?->setCurrentPage(1);
				$dashboardList = $superset->getDashboardRepository()->getList($ormParams);
			}
			else
			{
				return [];
			}
		}

		foreach ($dashboardList as $index => $dashboard)
		{
			$dashboardList[$index] = $dashboard->toArray();
		}

		return $dashboardList;
	}
}
