<?php

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Component\BaseComponent;
use Bitrix\Timeman\Component\ScheduleList\Grid;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Service\DependencyManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('timeman'))
{
	ShowError(htmlspecialcharsbx(Loc::getMessage('TIMEMAN_MODULE_NOT_INSTALLED')));
	return;
}
require __DIR__ . '/grid.php';

class ScheduleListComponent extends BaseComponent
{
	/** @var ScheduleRepository */
	private $scheduleRepository;
	private $componentId;
	/*** @var Grid */
	protected $grid;
	protected $gridId = 'tm-schedule-list';
	/** @var \Bitrix\Timeman\Security\UserPermissionsManager */
	private $userPermissionManager;

	public function __construct(\CBitrixComponent $component = null)
	{
		parent::__construct($component);
		$this->scheduleRepository = DependencyManager::getInstance()->getScheduleRepository();
		global $USER;
		$this->userPermissionManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
	}

	public function getComponentId()
	{
		if (!$this->componentId)
		{
			$this->componentId = 'tmschedulelist';
		}
		return $this->componentId;
	}

	public function executeComponent()
	{
		$this->getApplication()->setTitle(htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_TITLE')));
		if (!$this->userPermissionManager->canReadSchedules())
		{
			showError(htmlspecialcharsbx(Loc::getMessage('TM_SCHEDULE_LIST_ACCESS_DENIED')));
			return;
		}
		$grid = $this->getGrid();
		$this->arResult['GRID_ID'] = $this->gridId;
		$this->arResult['HEADERS'] = $grid->getHeaders();
		$schedules = $this->getSchedules();
		$this->arResult['ITEMS'] = $grid->getRows($schedules);
		$this->arResult['DEFAULT_PAGE_SIZE'] = $grid->getPageSize();

		$totalSchedulesCount = DependencyManager::getInstance()
			->getScheduleRepository()
			->getActiveSchedulesQuery()
			->addSelect('CNT')
			->registerRuntimeField(new Main\ORM\Fields\ExpressionField('CNT', 'COUNT(ID)'))
			->exec()
			->fetch();
		$this->getGrid()->getNavigation()->setRecordCount(reset(array_values($totalSchedulesCount)));

		$this->arResult['NAVIGATION'] = $this->getGrid()->getNavigation();

		$this->arResult['SHOW_ADD_SCHEDULE_BUTTON'] = $this->userPermissionManager->canCreateSchedule();
		$this->arResult['canDeleteSchedules'] = $this->userPermissionManager->canUpdateSchedules();
		$this->arResult['SHOW_CHECK_ALL_CHECKBOXES'] = $this->arResult['canDeleteSchedules'];
		$this->arResult['SHOW_ROW_CHECKBOXES'] = $this->arResult['canDeleteSchedules'];
		$this->arResult['SHOW_SELECTED_COUNTER'] = $this->arResult['canDeleteSchedules'];
		$this->arResult['SHOW_ACTION_PANEL'] = $this->arResult['canDeleteSchedules'];
		$arResult['SHOW_ROW_ACTIONS_MENU'] = false;

		$this->includeComponentTemplate();
	}

	private function getSchedules()
	{
		$gridOptions = new \CGridOptions($this->gridId);
		$gridSort = $gridOptions->getSorting(['sort' => ['ID' => 'desc']]);

		$query = DependencyManager::getInstance()
			->getScheduleRepository()
			->getActiveSchedulesQuery()
			->addSelect('ID');
		$this->arResult['SORT'] = $gridSort['sort'] ?: [];
		$this->arResult['SORT_VARS'] = $gridSort['vars'];
		$order = [];
		if ($this->arResult['SORT'] && in_array(reset(array_keys($this->arResult['SORT'])), $this->getGrid()->getSortableHeaders()))
		{
			$order[0] = reset(array_keys($this->arResult['SORT']));
			$order[1] = reset(array_values($this->arResult['SORT']));
		}
		else
		{
			$order[0] = 'ID';
		}
		$query->addOrder(...$order);
		$query->setLimit($this->getGrid()->getNavigation()->getLimit());
		$query->setOffset($this->getGrid()->getNavigation()->getOffset());
		$schedulesIds = array_column($query->exec()->fetchAll(), 'ID');

		if (!empty($schedulesIds))
		{
			return DependencyManager::getInstance()
				->getScheduleRepository()
				->getActiveSchedulesQuery()
				->addSelect('ID')
				->addSelect('NAME')
				->addSelect('SCHEDULE_TYPE')
				->addSelect('REPORT_PERIOD')
				->addSelect('REPORT_PERIOD')
				->addSelect('REPORT_PERIOD')
				->addSelect('SHIFTS.ID')
				->addSelect('DEPARTMENT_ASSIGNMENTS')
				->whereIn('ID', $schedulesIds)
				->where(Query::filter()->logic('or')
					->where('SHIFTS.DELETED', ShiftTable::DELETED_NO)
					->whereNull('SHIFTS.DELETED')
				)
				->addOrder(...$order)
				->exec()
				->fetchCollection();
		}

		return [];
	}

	/**
	 * Get component grid
	 *
	 * @return Grid
	 */
	protected function getGrid()
	{
		if ($this->grid === null)
		{
			$this->grid = new Grid($this->gridId, $this->scheduleRepository, $this->userPermissionManager);
		}

		return $this->grid;
	}
}