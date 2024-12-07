<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BiConnector\Settings\Grid\DashboardGrid;
use Bitrix\BiConnector\Settings\Grid\DashboardSettings;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\BIConnector;
use Bitrix\UI\Buttons;
use Bitrix\Main\UI\Filter;
use Bitrix\UI\Toolbar;
use Bitrix\UI\Toolbar\Facade;
use Bitrix\Main\Grid;
use Bitrix\BiConnector\Settings;

class DashboardListComponent extends CBitrixComponent implements Controllerable
{
	private CurrentUser $currentUser;
	private bool $canWrite;
	private bool $canRead;
	private DashboardGrid $grid;
	private ?Buttons\Button $addReportButton;
	private ?Filter\Options $filterOptions;

	private const GRID_ID = 'biconnector_dashboard_list';
	private const FILTER_FIELDS_ID = ['NAME', 'URL', 'DATE_CREATE', 'TIMESTAMP_X'];
	private const MODULE_NAME = 'biconnector';
	private const ONBOARDING_OPTION_NAME = 'onboarding_dashboard_list';

	public function __construct($component = null)
	{
		$this->currentUser = CurrentUser::get();
		$this->canWrite = $this->currentUser->canDoOperation('biconnector_dashboard_manage');
		$this->canRead = $this->canWrite || $this->currentUser->canDoOperation('biconnector_dashboard_view');
		parent::__construct($component);
	}

	public function executeComponent()
	{
		if (!$this->canWrite && !$this->canRead)
		{
			ShowError(Loc::getMessage('ACCESS_DENIED'));

			return;
		}

		if (!Loader::includeModule('biconnector'))
		{
			ShowError(Loc::getMessage('CC_BBDL_ERROR_INCLUDE_MODULE'));

			return;
		}

		$this->arResult['GRID_ID'] = self::GRID_ID;
		$this->prepareToolbar();
		$this->arResult['GRID'] = Grid\Component\ComponentParams::get($this->getGrid());
		$this->arResult['GRID']['STUB'] = isset($this->arResult['GRID']['ROWS'])
			&& empty($this->arResult['GRID']['ROWS']) ? $this->getGridEmptyStateBlock() : null;
		$this->arResult['ONBOARDING_BUTTON_ID'] = $this->getAddReportButton()->getUniqId();
		$this->arResult['IS_AVAILABLE_ONBOARDING'] = !$this->isOnboardingShowed();
		$this->includeComponentTemplate();
	}

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['DASHBOARD_LIST_URL'] ??= 'dashboard_list.php';
		$arParams['DASHBOARD_ADD_URL'] ??= 'dashboard_edit.php';
		$arParams['DASHBOARD_EDIT_URL'] ??= 'dashboard_edit.php?dashboard_id=#ID#';
		$arParams['DASHBOARD_VIEW_URL'] ??= 'dashboard.php?id=#ID#';

		return parent::onPrepareComponentParams($arParams);
	}

	private function getGrid(): DashboardGrid
	{
		if (!isset($this->grid))
		{
			$settings = new DashboardSettings([
				'ID' => self::GRID_ID,
				'ALLOW_ROWS_SORT' => false,
				'SHOW_ROW_CHECKBOXES' => false,
				'SHOW_SELECTED_COUNTER' => false,
				'SHOW_TOTAL_COUNTER' => false,
				'EDITABLE' => false,
				'CAN_WRITE' => $this->canWrite,
				'CAN_READ' => $this->canRead,
				'DASHBOARD_VIEW_URL' => $this->arParams['DASHBOARD_VIEW_URL'] ?? 'dashboard.php?id=#ID#',
				'DASHBOARD_EDIT_URL' => $this->arParams['DASHBOARD_EDIT_URL'] ?? 'dashboard_edit.php?dashboard_id=#ID#',
			]);

			$this->grid = new DashboardGrid($settings);
			$gridData = $this->getGridData($this->grid);
			$this->grid->setRawRows($gridData);
		}

		return $this->grid;
	}

	protected function getFilterConfig(): array
	{
		return [
			'FILTER_ID' => $this->getGrid()->getId(),
			'GRID_ID' => $this->getGrid()->getId(),
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => true,
			'THEME' => Filter\Theme::LIGHT,
			'FILTER' => $this->getGrid()->getFilter()?->getFieldArrays(self::FILTER_FIELDS_ID),
		];
	}

	protected function prepareToolbar(): void
	{
		Facade\Toolbar::addFilter($this->getFilterConfig());

		if ($this->canWrite)
		{
			Facade\Toolbar::addButton($this->getAddReportButton(), Toolbar\ButtonLocation::AFTER_TITLE);
		}

		Facade\Toolbar::addButton(new Settings\Buttons\Implementation());
		Facade\Toolbar::deleteFavoriteStar();
	}

	protected function getAddReportButton(): Buttons\Button
	{
		if (isset($this->addReportButton))
		{
			return $this->addReportButton;
		}

		$this->addReportButton = new Buttons\Button([
			'text' => Loc::getMessage('CC_BBDL_BUTTON_ADD_REPORT'),
			'color' => Buttons\Color::SUCCESS,
			'dataset' => [
				'toolbar-collapsed-icon' => Buttons\Icon::ADD,
			],
			'click' => new Buttons\JsCode(
				"top.BX.SidePanel.Instance.open('{$this->arParams['DASHBOARD_ADD_URL']}', {width: 650, loader: 'biconnector:create-dashboard'})"
			),
			'id' => 'add-report-button-id',
		]);

		return $this->addReportButton;
	}

	protected function getGridData(DashboardGrid $grid): array
	{
		$dashboardQuery = BIConnector\DashboardTable::query()->setSelect([
			'ID',
			'DATE_CREATE',
			'DATE_LAST_VIEW',
			'CREATED_BY',
			'CREATED_USER.NAME',
			'CREATED_USER.LAST_NAME',
			'CREATED_USER.SECOND_NAME',
			'CREATED_USER.EMAIL',
			'CREATED_USER.LOGIN',
			'CREATED_USER.PERSONAL_PHOTO',
			'LAST_VIEW_BY',
			'LAST_VIEW_USER.NAME',
			'LAST_VIEW_USER.LAST_NAME',
			'LAST_VIEW_USER.SECOND_NAME',
			'LAST_VIEW_USER.EMAIL',
			'LAST_VIEW_USER.LOGIN',
			'LAST_VIEW_USER.PERSONAL_PHOTO',
			'TIMESTAMP_X',
			'NAME',
			'URL',
		])->setOrder($grid->getOrmOrder())->setFilter($grid->getOrmFilter());

		if (!$this->canWrite)
		{
			$dashboardQuery->addFilter('=PERMISSION.USER_ID', $this->currentUser->getId());
		}

		$searchString = $this->getFilterOptions()->getSearchString();

		if ($searchString !== '')
		{
			$dashboardQuery->addFilter('%NAME', $searchString);
		}

		return $dashboardQuery->exec()->fetchAll();
	}

	private function getFilterOptions(): Filter\Options
	{
		if (!empty($this->filterOptions))
		{
			return $this->filterOptions;
		}

		$this->filterOptions = new Filter\Options(self::GRID_ID);

		return $this->filterOptions;
	}

	protected function getGridEmptyStateBlock(): string
	{
		$title = Loc::getMessage('CC_BBDL_EMPTYSTATE_TITLE');
		$subtitle = Loc::getMessage('CC_BBDL_EMPTYSTATE_SUBTITLE');

		return "
			<div class=\"biconnector-empty\">
				<div class=\"biconnector-empty__icon --report\"></div>
				<div class=\"biconnector-empty__title\">{$title}</div>
				<div class=\"biconnector-empty__title-sub\">{$subtitle}</div>
			</div>
		";
	}

	public function deleteRowAction(int $id): bool
	{
		if ($this->canWrite && Loader::includeModule('biconnector'))
		{
			BIConnector\DashboardUserTable::deleteByFilter(['=DASHBOARD_ID' => $id]);
			return BIConnector\DashboardTable::delete($id)->isSuccess();
		}

		return false;
	}

	public function markShowOnboardingAction(): void
	{
		CUserOptions::setOption(self::MODULE_NAME, self::ONBOARDING_OPTION_NAME, time());
	}

	public function isOnboardingShowed(): bool
	{
		return (bool)CUserOptions::getOption(self::MODULE_NAME, self::ONBOARDING_OPTION_NAME);
	}

	public function configureActions(): array
	{
		return [];
	}
}
