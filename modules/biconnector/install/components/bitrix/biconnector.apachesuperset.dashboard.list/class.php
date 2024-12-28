<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Configuration\DashboardTariffConfigurator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTagTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardUrlParameterTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetScopeTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Grid\DashboardGrid;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\BIConnector\Superset\UI\UIHelper;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\UI\Buttons;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons\JsCode;

class ApacheSupersetDashboardListComponent extends CBitrixComponent
{
	private const GRID_ID = 'biconnector_superset_dashboard_grid';

	private DashboardGrid $grid;
	private SupersetController $supersetController;

	public function onPrepareComponentParams($arParams)
	{
		$arParams['ID'] = (int)($arParams['ID'] ?? 0);
		$arParams['CODE'] = $arParams['CODE'] ?? '';

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$this->init();
		$this->grid->processRequest();
		$this->grid->setSupersetAvailability($this->getSupersetController()->isExternalServiceAvailable());

		if (SupersetInitializer::getSupersetStatus() === SupersetInitializer::SUPERSET_STATUS_READY)
		{
			$manager = MarketDashboardManager::getInstance();
			$manager->updateApplications();
		}
		$this->loadRows();

		$this->arResult['GRID'] = $this->grid;

		$this->includeComponentTemplate();
	}

	private function init(): void
	{
		$this->initGrid();
		$this->initGridFilter();
		$this->initCreateButton();
		$this->initToolbar();
		$this->arResult['SHOW_DELETE_INSTANCE_BUTTON'] = UIHelper::needShowDeleteInstanceButton();
		$this->arResult['NEED_SHOW_TOP_MENU_GUIDE'] = $this->isNeedShowGuide('top_menu_guide');
		$this->arResult['NEED_SHOW_DRAFT_GUIDE'] = $this->isNeedShowGuide('draft_guide');
	}

	private function initGrid(): void
	{
		$settings = new DashboardSettings([
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
			$this->grid->getOptions()->setSorting('ID', 'desc');
		}

		$query = SupersetDashboardTable::query()
			->setSelect(['ID'])
			->setCacheTtl(3600)
		;

		$ormParams = $this->getOrmParams();
		$query->setFilter($ormParams['filter']);
		if (
			!empty($ormParams['filter']['TAGS.ID'])
			&& is_array($ormParams['filter']['TAGS.ID'])
			&& count($ormParams['filter']['TAGS.ID']) > 1
		)
		{
			$query->setGroup('ID');
		}
		$totalCount = $query->queryCountTotal();
		$grid->initPagination($totalCount);
	}

	private function initGridFilter(): void
	{
		$filter = $this->grid->getFilter();
		if ($filter)
		{
			$options = \Bitrix\Main\Filter\Component\ComponentParams::get(
				$this->grid->getFilter(),
				[
					'GRID_ID' => $this->grid->getId(),
				]
			);
		}
		else
		{
			$options = [
				'FILTER_ID' => $this->grid->getId(),
			];
		}

		Toolbar::addFilter($options);
	}

	private function getOrmParams(): array
	{
		$ormParams = $this->grid->getOrmParams();

		$accessFilter = $this->getAccessFilter();
		if ($accessFilter)
		{
			$ormParams['filter'] = [
				$accessFilter,
				$ormParams['filter'],
			];
		}

		if (
			!empty($ormParams['filter']['TAGS.ID'])
			&& is_array($ormParams['filter']['TAGS.ID'])
			&& count($ormParams['filter']['TAGS.ID']) > 1
		)
		{
			$subQuery = SupersetDashboardTagTable::query()
				->setSelect(['DASHBOARD_ID', 'COUNT'])
				->registerRuntimeField('COUNT', new ExpressionField('COUNT', 'COUNT(1)'))
				->whereIn('TAG_ID', $ormParams['filter']['TAGS.ID'])
				->addGroup('DASHBOARD_ID')
			;

			$ormParams['runtime'] ??= [];
			$ormParams['runtime'][] = new Reference(
				'SUBQUERY',
				Base::getInstanceByQuery($subQuery),
				['this.ID' => 'ref.DASHBOARD_ID'],
				['join_type' => 'INNER']
			);

			$ormParams['filter']['=SUBQUERY.COUNT'] = count($ormParams['filter']['TAGS.ID']);
		}

		$pinnedDashboardIds = CUserOptions::GetOption('biconnector', 'grid_pinned_dashboards', []);
		Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($pinnedDashboardIds);
		if (!empty($pinnedDashboardIds))
		{
			$ormParams['runtime'][] = new ExpressionField(
				'IS_PINNED',
				'CASE WHEN %s IN (' . implode(',', $pinnedDashboardIds) . ') THEN 1 ELSE 0 END',
				['ID'],
				['data_type' => 'integer']
			);
			$ormParams['order'] = ['IS_PINNED' => 'DESC'] + $ormParams['order'];
			$ormParams['select'][] = 'IS_PINNED';
		}

		return $ormParams;
	}

	private function loadRows(): void
	{
		$rows = $this->getSupersetRows($this->getOrmParams());
		$this->grid->setRawRows($rows);
	}

	private function initCreateButton(): void
	{
		\Bitrix\Main\UI\Extension::load('biconnector.apache-superset-market-manager');
		$isMarketExists = Loader::includeModule('market') ? 'true' : 'false';
		$marketUrl = CUtil::JSEscape(MarketDashboardManager::getMarketCollectionUrl());
		$openMarketScript = "BX.BIConnector.ApacheSupersetMarketManager.openMarket({$isMarketExists}, '{$marketUrl}', 'menu')";

		$splitButton = new Buttons\Split\CreateButton([
			'dataset' => [
				'toolbar-collapsed-icon' => Buttons\Icon::ADD,
			],
		]);

		$mainButton = $splitButton->getMainButton();
		$mainButton->getAttributeCollection()['onclick'] = $openMarketScript;

		$menuItems = [
			[
				'text' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_LIST_MENU_ITEM_CREATE_DASHBOARD'),
				'onclick' => new JsCode($openMarketScript),
			],
		];

		if (AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_CREATE))
		{
			$menuItems[] = [
				'text' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_LIST_MENU_ITEM_NEW_DASHBOARD'),
				'onclick' => new Bitrix\UI\Buttons\JsCode(
					'this.close(); BX.BIConnector.SupersetDashboardGridManager.Instance.openCreationSlider()'
				),
			];
		}

		$splitButton->setMenu([
			'items' => $menuItems,
			'closeByEsc' => true,
			'angle' => true,
			'offsetLeft' => 115,
			'autoHide' => true,
		]);

		Toolbar::addButton($splitButton, ButtonLocation::AFTER_TITLE);
	}

	private function initToolbar(): void
	{
		if (UIHelper::needShowDeleteInstanceButton())
		{
			$clearButton = new Button([
				'color' => Color::DANGER,
				'text' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_LIST_CLEAR_BUTTON'),
				'click' => new JsCode(
					'BX.BIConnector.ApacheSupersetTariffCleaner.Instance.handleButtonClick(this)'
				),
			]);
			Toolbar::addButton($clearButton);
		}
	}

	/**
	 * @param array $ormParams
	 * @return Dashboard[]
	 */
	private function getSupersetRows(array $ormParams): array
	{
		$superset = $this->getSupersetController();
		$dashboardList = $superset->getDashboardRepository()->getList($ormParams, true);
		if (!$dashboardList)
		{
			if ($ormParams['offset'] !== 0)
			{
				$ormParams['offset'] = 0;
				$this->grid->getPagination()?->setCurrentPage(1);
				$dashboardList = $superset->getDashboardRepository()->getList($ormParams, true);
			}
			else
			{
				return [];
			}
		}

		$dashboardIds = [];
		foreach ($dashboardList as $index => $dashboard)
		{
			$dashboardList[$index] = $dashboard->toArray();
			$dashboardIds[$dashboard->getId()] = $index;
			$urlService = new UrlParameter\Service($dashboard->getOrmObject());
			$dashboardList[$index]['DETAIL_URL'] = $urlService->getEmbeddedUrl();
			$appId = $dashboardList[$index]['APP_ID'];
			$dashboardList[$index]['IS_TARIFF_RESTRICTED'] = !empty($appId)	&& !DashboardTariffConfigurator::isAvailableDashboard($appId);
			$dashboardList[$index]['HAS_ZONE_URL_PARAMS'] = $urlService->isExistScopeParams();
			$dashboardList[$index]['IS_AVAILABLE_DASHBOARD'] = $dashboard->isAvailableDashboard();
			if ($dashboard->getOrmObject()->getSource())
			{
				$urlSourceService = new UrlParameter\Service($dashboard->getOrmObject()->getSource());
				$dashboardList[$index]['SOURCE_DETAIL_URL'] = $urlSourceService->getEmbeddedUrl();
				$dashboardList[$index]['SOURCE_HAS_ZONE_URL_PARAMS'] = $urlSourceService->isExistScopeParams();
			}
		}

		$dashboardTagsQuery = SupersetDashboardTable::getList([
			'filter' => [
				'=ID' => array_keys($dashboardIds),
			],
			'select' => ['TAGS', 'ID']
		]);

		foreach ($dashboardTagsQuery->fetchCollection() as $dashboard)
		{
			$tagList = [];
			$tags = $dashboard->getTags();
			foreach ($tags->getAll() as $tag)
			{
				$tagList[] = [
					'ID' => $tag->getId(),
					'TITLE' => $tag->getTitle(),
				];
			}

			$index = $dashboardIds[$dashboard->getId()];
			$dashboardList[$index]['TAGS'] = $tagList;
		}

		$dashboardsInTopMenu = CUserOptions::getOption('biconnector', 'top_menu_dashboards');
		foreach ($dashboardsInTopMenu as $dashboardId)
		{
			$index = $dashboardIds[$dashboardId] ?? null;
			if ($index !== null)
			{
				$dashboardList[$index]['IS_IN_TOP_MENU'] = true;
			}
		}

		$pinnedDashboards = CUserOptions::getOption('biconnector', 'grid_pinned_dashboards', []);
		foreach ($pinnedDashboards as $dashboardId)
		{
			$index = $dashboardIds[$dashboardId] ?? null;
			if ($index !== null)
			{
				$dashboardList[$index]['IS_PINNED'] = true;
			}
		}

		$dashboardScopeQuery = SupersetScopeTable::getList([
			'select' => ['SCOPE_CODE', 'DASHBOARD_ID', 'IS_AUTOMATED_SOLUTION'],
			'filter' => ['=DASHBOARD_ID' => array_keys($dashboardIds)],
			'order' => ['IS_AUTOMATED_SOLUTION' => 'asc', 'SCOPE_CODE' => 'asc'],
			'runtime' => [
				new ExpressionField(
					'IS_AUTOMATED_SOLUTION',
					"CASE WHEN %s LIKE 'automated_solution_%%' THEN 1 ELSE 0 END",
					['SCOPE_CODE'],
					['data_type' => 'integer']
				),
			],
		]);
		foreach ($dashboardScopeQuery->fetchCollection() as $scope)
		{
			$dashboardList[$dashboardIds[$scope->getDashboardId()]]['SCOPE'][] = $scope->getScopeCode();
		}

		$dashboardUrlParamsQuery = SupersetDashboardUrlParameterTable::getList([
			'select' => ['CODE', 'DASHBOARD_ID'],
			'filter' => ['=DASHBOARD_ID' => array_keys($dashboardIds)],
		]);
		foreach ($dashboardUrlParamsQuery->fetchCollection() as $variable)
		{
			$parameter = UrlParameter\Parameter::tryFrom($variable->getCode());
			if ($parameter)
			{
				$dashboardList[$dashboardIds[$variable->getDashboardId()]]['URL_PARAMS'][] = $parameter->title();
			}
		}

		return $dashboardList;
	}

	private function getAccessFilter(): ?array
	{
		return AccessController::getCurrent()->getEntityFilter(
			ActionDictionary::ACTION_BIC_DASHBOARD_VIEW,
			SupersetDashboardTable::class
		);
	}

	private function getSupersetController(): SupersetController
	{
		if (!isset($this->supersetController))
		{
			$this->supersetController = new SupersetController(Integrator::getInstance());
		}

		return $this->supersetController;
	}

	private function isNeedShowGuide(string $guideName): bool
	{
		if (!SupersetInitializer::isSupersetReady())
		{
			return false;
		}

		if ((int)$this->grid->getPagination()?->getRecordCount() <= 0)
		{
			return false;
		}

		$guideOption = CUserOptions::GetOption('biconnector', $guideName);
		if (!is_array($guideOption))
		{
			return true;
		}

		return !$guideOption['is_over'];
	}
}
