<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Update\DashboardGroupRights\Converter;
use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\Repository\DashboardGroupRepository;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Grid\DashboardGrid;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\BIConnector\Superset\MarketDashboardManager;
use Bitrix\BIConnector\Superset\SystemDashboardManager;
use Bitrix\BIConnector\Superset\UI\UIHelper;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons\JsCode;

class ApacheSupersetDashboardListComponent extends CBitrixComponent
{
	private DashboardGrid $grid;
	private SupersetController $supersetController;

	public function onPrepareComponentParams($arParams)
	{
		$arParams['ID'] = (int)($arParams['ID'] ?? 0);
		$arParams['CODE'] ??= '';
		$arParams['IS_MARKET_EXISTS'] = Loader::includeModule('market');
		$arParams['MARKET_URL'] = MarketDashboardManager::getMarketCollectionUrl();

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$dashboardsExist = SupersetDashboardTable::getList([
			'select' => ['ID'],
			'limit' => 1,
			'cache' => ['ttl' => 864000],
		])
			->fetch()
		;

		if (!$dashboardsExist)
		{
			SystemDashboardManager::actualizeSystemDashboards();
		}

		if (!Feature::isCheckPermissionsByGroup())
		{
			Converter::updateToGroup(true);
		}

		$this->init();
		$this->grid->processRequest();
		if (SupersetInitializer::isSupersetExist())
		{
			$this->grid->setSupersetAvailability($this->getSupersetController()->isExternalServiceAvailable());
		}

		$this->loadRows();

		$this->arResult['GRID'] = $this->grid;

		$this->includeComponentTemplate();

		Application::getInstance()->addBackgroundJob(function() {
			MarketDashboardManager::getInstance()->updateApplications();
		});
	}

	private function init(): void
	{
		$this->initGrid();
		$this->initGridFilter();
		$this->initCreateButton();
		$this->initToolbar();
		$this->arResult['SHOW_DELETE_INSTANCE_BUTTON'] = UIHelper::needShowDeleteInstanceButton();
		$this->arResult['NEED_SHOW_DRAFT_GUIDE'] = $this->isNeedShowGuide('draft_guide');
		$this->arResult['IS_AVAILABLE_DASHBOARD_CREATION'] = AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_CREATE);
		$this->arResult['IS_AVAILABLE_GROUP_CREATION'] = AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_GROUP_MODIFY);
		$this->arResult['SUPERSET_STATUS'] = SupersetInitializer::getSupersetStatus();
	}

	private function initGrid(): void
	{
		$settings = new DashboardSettings([
			'ID' => DashboardGrid::SUPERSET_DASHBOARD_GRID_ID,
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

		$superset = $this->getSupersetController();

		$grid->initPagination($superset->getUnionDashboardGroupRepository()->getCount($this->getOrmParams()));
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
				],
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
		$ormParams['runtime'] ??= [];

		if (empty($ormParams['filter']) || !is_array($ormParams['filter']))
		{
			$ormParams['filter'] = [
				[
					'LOGIC' => 'OR',
					'GROUPS.ID' => null,
					'=ENTITY_TYPE' => DashboardGroupRepository::TYPE_GROUP,
				],
			];
		}

		$ormParams['filter'] = array_merge($this->getAccessFilter($ormParams['filter']), $ormParams['filter']);

		$pinnedDashboardIds = CUserOptions::GetOption('biconnector', 'grid_pinned_dashboards', []);
		Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($pinnedDashboardIds);
		if (!empty($pinnedDashboardIds))
		{
			$ormParams['runtime'][] = new ExpressionField(
				'IS_PINNED',
				'CASE WHEN %s IN (' . implode(',', $pinnedDashboardIds) . ') THEN 1 ELSE 0 END',
				['ID'],
				['data_type' => 'integer'],
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
		Extension::load('biconnector.apache-superset-market-manager');
		$isMarketExists = $this->arParams['IS_MARKET_EXISTS'] ? 'true' : 'false';
		$marketUrl = CUtil::JSEscape($this->arParams['MARKET_URL']);
		$openMarketScript = "BX.BIConnector.ApacheSupersetMarketManager.openMarket({$isMarketExists}, '{$marketUrl}', 'menu')";

		$splitButton = new Buttons\Split\CreateButton([
			'dataset' => [
				'toolbar-collapsed-icon' => Buttons\Icon::ADD,
			],
		]);

		$mainButton = $splitButton->getMainButton();
		$mainButton->getAttributeCollection()['onclick'] = $openMarketScript;

		$menuButton = $splitButton->getMenuButton();
		$showMenuScript = "BX.BIConnector.SupersetDashboardGridManager.Instance.showCreationMenu(event)";
		$menuButton->getAttributeCollection()['onclick'] = $showMenuScript;
		$menuButton->setId('biconnector-creation-entity-button');

		$splitButton->getAttributeCollection()->addJsonOption(
			'menuTarget',
			\Bitrix\UI\Buttons\Split\Type::MENU,
		);

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
					'BX.BIConnector.ApacheSupersetTariffCleaner.Instance.handleButtonClick(this)',
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
		$needLoadProxyData = SupersetInitializer::isSupersetExist();
		$dashboardList = $superset->getUnionDashboardGroupRepository()->getList($ormParams, $needLoadProxyData);
		if (!$dashboardList)
		{
			if ($ormParams['offset'] !== 0)
			{
				$ormParams['offset'] = 0;
				$this->grid->getPagination()?->setCurrentPage(1);
				$dashboardList = $superset->getUnionDashboardGroupRepository()->getList($ormParams, $needLoadProxyData);
			}
			else
			{
				return [];
			}
		}

		return $dashboardList;
	}

	private function getAccessFilter(array $currentFilter): ?array
	{
		$result = [];

		$dashboardFilter = AccessController::getCurrent()->getEntityFilter(
			ActionDictionary::ACTION_BIC_DASHBOARD_VIEW,
			SupersetDashboardTable::class,
		);

		if (!empty($dashboardFilter))
		{
			$result = ['DASHBOARD_ID' => $dashboardFilter['=ID']];
		}

		$groupFilter = AccessController::getCurrent()->getEntityFilter(
			ActionDictionary::ACTION_BIC_DASHBOARD_VIEW,
			SupersetDashboardGroupTable::class,
		);

		if (empty($groupFilter))
		{
			return $result;
		}

		$groupIdFilter = $groupFilter['=ID'] ?? [];

		if (isset($currentFilter['GROUPS.ID']) && is_array($currentFilter['GROUPS.ID']))
		{
			$groupIdFilter = array_intersect($groupIdFilter, $currentFilter['GROUPS.ID']);
			if (!empty($groupIdFilter))
			{
				unset($result['DASHBOARD_ID']);
				$result[] = [
					'LOGIC' => 'OR',
					'!=STATUS' => SupersetDashboardTable::DASHBOARD_STATUS_DRAFT,
					'=OWNER_ID' => (int)CurrentUser::get()->getId(),
				];
			}
		}

		$result['GROUP_ID'] = $groupIdFilter;

		return $result;
	}

	private function getSupersetController(): SupersetController
	{
		if (!isset($this->supersetController))
		{
			$this->supersetController = new SupersetController(Integrator::getInstance());
			if (SupersetInitializer::isSupersetExist())
			{
				$this->supersetController->isExternalServiceAvailable();
			}
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
