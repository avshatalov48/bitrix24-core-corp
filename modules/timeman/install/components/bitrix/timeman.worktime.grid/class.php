<?php
namespace Bitrix\Timeman\Component\SchedulePlan;

use \Bitrix\Main;
use Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\Date;
use \Bitrix\Timeman;
use Bitrix\Timeman\Component\WorktimeGrid\Grid;
use Bitrix\Timeman\Component\WorktimeGrid\TemplateParams;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Component\WorktimeGrid\Ranges;
use Bitrix\Timeman\TimemanUrlManager;

require_once __DIR__ . '/grid.php';
require_once __DIR__ . '/templateparams.php';
require_once __DIR__ . '/ranges.php';
require_once __DIR__ . '/normalizer.php';

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('timeman') ||
	!Main\Loader::includeModule('intranet'))
{
	showError(Loc::getMessage('TIMEMAN_MODULE_NOT_INSTALLED'));
	return;
}

class TimemanWorktimeGridComponent extends Timeman\Component\BaseComponent
{
	/** @var array */
	protected $schedule;
	private $grid;
	private $gridId = 'TM_WORKTIME_GRID';
	/** @var Timeman\Security\UserPermissionsManager */
	private $userPermissionsManager;
	private $dateTimeFormat;
	/** @var Timeman\Model\Schedule\ScheduleCollection */
	private $scheduleCollection = null;
	/** @var array */
	private $shiftPlansByUserShiftDate;
	/** @var array */
	private $recordsByUsersDates = [];
	private $dependencyManager;
	private $departmentRepository;
	private $dateTimeTo;
	private $dateTimeFrom;
	/** @var Timeman\Model\User\UserCollection */
	private $usersCollection;
	/** @var Timeman\Model\User\User */
	private $currentUser;

	public function __construct($component = null)
	{
		global $USER;
		$this->dateTimeFormat = ShiftPlanTable::DATE_FORMAT;
		$this->dependencyManager = DependencyManager::getInstance();
		$this->departmentRepository = $this->dependencyManager->getDepartmentRepository();
		$this->userPermissionsManager = $this->dependencyManager->getUserPermissionsManager($USER);
		$this->usersCollection = new Timeman\Model\User\UserCollection();

		parent::__construct($component);
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->arResult['timeHelper'] = TimeHelper::getInstance();
		$this->arResult['SCHEDULE_ID'] = $this->getFromParamsOrRequest($arParams, 'SCHEDULE_ID', 'int');

		return $arParams;
	}

	public function executeComponent()
	{
		$this->initViewResult();
		if (!$this->currentUser)
		{
			showError(empty(Loc::getMessage('TM_WORKTIME_STATS_ACCESS_DENIED')) ?
				Loc::getMessage('TM_WORKTIME_STATS_SCHEDULE_NOT_FOUND') :
				Loc::getMessage('TM_WORKTIME_STATS_ACCESS_DENIED'));
			return;
		}
		if ($this->arResult['PARTIAL_ITEM'] === 'shiftCell')
		{
			Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/js_core.php');
			$this->arResult['templateParamsList'] = $this->buildParamsForPartialView();
			$this->includeComponentTemplate('day-cell');
			return;
		}


		if ($this->arResult['SCHEDULE_ID'] > 0)
		{
			$this->scheduleCollection = $this->fetchSchedules([$this->arResult['SCHEDULE_ID']]);
			if ($this->scheduleCollection->count() == 0 ||
				($this->arResult['IS_SHIFTPLAN'] && !$this->scheduleCollection->getFirst()->isShifted()))
			{
				showError(Loc::getMessage('TM_WORKTIME_STATS_SCHEDULE_NOT_FOUND'));
				return;
			}
		}

		$departmentsToUsersMap = $this->buildDepartmentsToUsersMap();
		$this->applyAccessControlToUserIds($departmentsToUsersMap);
		$this->applyFiltersToUserIds($departmentsToUsersMap);
		$departmentsToUsersMap = $this->sortUserIds($departmentsToUsersMap);
		$this->setTotalCount($departmentsToUsersMap);
		$this->setLimitOffset($departmentsToUsersMap);
		$this->arResult['DEPARTMENT_USERS_DATA'] = $this->fillDepartmentUsersData($departmentsToUsersMap);
		$this->setTimezoneToggleAvailable($departmentsToUsersMap);
		$this->fillScheduleRecordsPlans($this->extractUserIds($departmentsToUsersMap));

		$grid = $this->getGrid();
		$this->arResult['NAV_OBJECT'] = $grid->getNavigation();
		$grid->getNavigation()->setRecordCount($this->arResult['TOTAL_USERS_COUNT']);
		$this->arResult['FILTER'] = $grid->getFilter();
		$this->arResult['HEADERS'] = $grid->getHeaders();
		$this->arResult['DATES'] = $grid->getPeriodDates();

		$this->getGrid()->fillRowsDataWithTemplateParams(
			$this->arResult['DEPARTMENT_USERS_DATA'],
			$this->usersCollection,
			$this->recordsByUsersDates,
			$this->scheduleCollection,
			$this->shiftPlansByUserShiftDate,
			$this->findViolationRules($departmentsToUsersMap)
		);

		if ($this->arResult['GRID_OPTIONS']['SHOW_STATS_COLUMNS'])
		{
			$this->arResult['WORKTIME_STATISTICS'] = $grid->getWorktimeStatistics($this->arResult['DEPARTMENT_USERS_DATA']);
		}

		$this->makeUrls();
		$this->initGridOptions();
		$this->arResult['usersCollection'] = $this->usersCollection;
		$this->includeComponentTemplate();
	}

	private function findViolationRules($departmentsToUsersMap)
	{
		$userIds = $this->extractUserIds($departmentsToUsersMap);
		if ($this->scheduleCollection->count() == 0 || empty($userIds))
		{
			return [];
		}
		$depChain = [];
		$entitiesCodes = [];
		foreach ($userIds as $userId)
		{
			$depChain[$userId] = $this->departmentRepository->buildUserDepartmentsPriorityTree($userId);
			foreach ($depChain[$userId] as $treeData)
			{
				$entitiesCodes = array_merge($entitiesCodes, $treeData);
			}
		}
		$entitiesCodes = array_unique($entitiesCodes);
		$schedulesIds = $this->scheduleCollection->getIdList();

		if (empty($entitiesCodes))
		{
			return [];
		}
		$violationRulesCollection = Timeman\Model\Schedule\Violation\ViolationRulesTable::query()
			->addSelect('*')
			->whereIn('ENTITY_CODE', $entitiesCodes)
			->whereIn('SCHEDULE_ID', $schedulesIds)
			->exec()
			->fetchCollection();
		$violationRulesList = [];
		foreach ($violationRulesCollection as $violationRules)
		{
			/** @var Timeman\Model\Schedule\Violation\ViolationRules $violationRules */
			$violationRulesList[$violationRules->getEntityCode()][] = $violationRules;
		}
		$userViolationsMap = [];
		foreach ($depChain as $userId => $userChainData)
		{
			foreach ($userChainData as $userChain)
			{
				foreach ($userChain as $entCode)
				{
					if (!empty($violationRulesList[$entCode]))
					{
						$userViolationsMap[$userId] = $violationRulesList[$entCode];
						break;
					}
				}
			}
		}
		return $userViolationsMap;
	}

	private function showError($errorMessage)
	{
		$this->addError($errorMessage);
		$this->includeComponentTemplate('error');
	}

	private function addError($errorMessage)
	{
		$this->arResult['errorMessages'][] = $errorMessage;
	}

	/**
	 * @param $ids
	 * @return Timeman\Model\Schedule\ScheduleCollection
	 */
	protected function fetchSchedules($ids)
	{
		$res = Timeman\Model\Schedule\ScheduleTable::query()
			->addSelect('*')
			->addSelect('SCHEDULE_VIOLATION_RULES')
			->addSelect('DEPARTMENT_ASSIGNMENTS')
			->addSelect('USER_ASSIGNMENTS')
			->addSelect('ALL_SHIFTS.*');

		return $res->whereIn('ID', $ids)
			->exec()
			->fetchCollection();
	}

	private function getDateTimeTo()
	{
		return $this->dateTimeTo ? $this->dateTimeTo : $this->getGrid()->getDateTimeTo();
	}

	private function getDateTimeFrom()
	{
		return $this->dateTimeFrom ? $this->dateTimeFrom : $this->getGrid()->getDateTimeFrom();
	}

	private function fillScheduleRecordsPlans($userIds)
	{
		$this->recordsByUsersDates = [];
		$this->shiftPlansByUserShiftDate = [];

		if (empty($userIds))
		{
			return;
		}
		$recordsRaw = WorktimeRecordTable::query()
			->addSelect('ID')
			->addSelect('USER_ID')
			->addSelect('DURATION')
			->addSelect('TIME_LEAKS')
			->addSelect('RECORDED_START_TIMESTAMP')
			->addSelect('ACTUAL_START_TIMESTAMP')
			->addSelect('START_OFFSET')
			->addSelect('RECORDED_STOP_TIMESTAMP')
			->addSelect('ACTUAL_STOP_TIMESTAMP')
			->addSelect('STOP_OFFSET')
			->addSelect('CURRENT_STATUS')
			->addSelect('ACTUAL_BREAK_LENGTH')
			->addSelect('SCHEDULE_ID')
			->addSelect('SHIFT_ID')
			->addSelect('RECORDED_DURATION')
			->addSelect('APPROVED')
			->whereIn('USER_ID', $userIds);
		$this->addFilterRecordsByDates($recordsRaw);
		if ($this->getGrid()->isFilterByApprovedApplied())
		{
			$recordsRaw->where('APPROVED', $this->getGrid()->getFilterByApproved() !== 'Y');
		}
		if ($this->arResult['SCHEDULE_ID'] > 0)
		{
			$recordsRaw->where('SCHEDULE_ID', $this->arResult['SCHEDULE_ID']);
		}
		if ($this->getGrid()->isSchedulesFilterApplied())
		{
			$recordsRaw->whereIn('SCHEDULE_ID', $this->getGrid()->getFilteredSchedulesIds());
		}
		/** @var Timeman\Model\Worktime\Record\WorktimeRecordCollection $recordsCollection */
		$recordsCollection = $recordsRaw->exec()->fetchCollection();
		foreach ($recordsCollection as $record)
		{
			$startDate = TemplateParams::buildDateInShowingTimezone($record->getRecordedStartTimestamp(), $record->getUserId(), $this->currentUser->getId());
			if ($startDate)
			{
				$this->recordsByUsersDates[$record->getUserId()][$startDate->format($this->dateTimeFormat)][$record->getId()] = $record;
			}
		}

		$scheduleIds = $recordsCollection->getScheduleIdList();
		$schedulesMap = $this->dependencyManager->getScheduleRepository()
			->findSchedulesByEntityCodes(EntityCodesHelper::buildUserCodes($userIds));
		foreach ($schedulesMap as $userCode => $schedules)
		{
			foreach ($schedules as $schedule)
			{
				$scheduleIds[] = $schedule->getId();
			}
		}
		$scheduleIds = array_unique($scheduleIds);
		if ($this->arResult['SCHEDULE_ID'] > 0 && $this->scheduleCollection->getByPrimary($this->arResult['SCHEDULE_ID']))
		{
			$sId = (int)$this->arResult['SCHEDULE_ID'];
			$scheduleIds = array_filter($scheduleIds, function ($id) use ($sId) {
				return $sId !== (int)$id;
			});
		}

		$scheduleIdsFromShiftPlansQuery = $this->dependencyManager->getShiftPlanRepository()
			->getActivePlansQuery()
			->registerRuntimeField(new ExpressionField('UNIQUE_SCHEDULE_IDS', 'DISTINCT SCHEDULE_ID'))
			->registerRuntimeField(
				(new \Bitrix\Main\ORM\Fields\Relations\Reference('SHIFT_OF_PLANS', ShiftTable::class, ['this.SHIFT_ID' => 'ref.ID']))
					->configureJoinType('INNER')
			)
			->addSelect('UNIQUE_SCHEDULE_IDS');
		$this->addFilterPlansByDates($scheduleIdsFromShiftPlansQuery);
		$scheduleIdsFromShiftPlansQuery->whereIn('USER_ID', $userIds);
		$scheduleIdsFromShiftPlans = $scheduleIdsFromShiftPlansQuery->exec()->fetchAll();
		if (count($scheduleIdsFromShiftPlans) > 0)
		{
			$scheduleIds = array_merge($scheduleIds, array_map('intval', array_column($scheduleIdsFromShiftPlans, 'UNIQUE_SCHEDULE_IDS')));
			$scheduleIds = array_unique($scheduleIds);
		}
		if (!empty($scheduleIds))
		{
			$schedulesCollection = $this->fetchSchedules($scheduleIds);
			if ($this->scheduleCollection->count() === 0)
			{
				$this->scheduleCollection = $schedulesCollection;
			}
			else
			{
				foreach ($schedulesCollection as $schedule)
				{
					if (!$this->scheduleCollection->getByPrimary($schedule->getId()))
					{
						$this->scheduleCollection->add($schedule);
					}
				}
			}
		}

		if ($this->getDateTimeFrom() && $this->getDateTimeTo() && $this->scheduleCollection->hasShifted())
		{
			$shiftedScheduleIds = [];
			foreach ($this->scheduleCollection as $schedule)
			{
				if ($schedule->isShifted())
				{
					$shiftedScheduleIds[] = $schedule->getId();
				}
			}
			$resPlans = $this->dependencyManager
				->getShiftPlanRepository()
				->getActivePlansQuery()
				->addSelect('ID')
				->addSelect('SHIFT_ID')
				->addSelect('DATE_ASSIGNED')
				->addSelect('USER_ID')
				->addSelect('DELETED')
				->addSelect('SHIFT.ID', 'SH_ID')
				->addSelect('SHIFT.WORK_TIME_START')
				->addSelect('SHIFT.SCHEDULE_WITH_ALL_SHIFTS')
				->whereIn('SHIFT.SCHEDULE_WITH_ALL_SHIFTS.ID', $shiftedScheduleIds)
				->whereIn('USER_ID', $userIds);
			$this->addFilterPlansByDates($resPlans);
			$resPlans = $resPlans
				->exec()
				->fetchCollection();
			foreach ($resPlans as $shiftPlan)
			{
				/** @var Timeman\Model\Schedule\ShiftPlan\ShiftPlan $shiftPlan */
				$utcStartDateTime = $shiftPlan->buildShiftStartDateTimeUtc();
				$dateTimeStart = TemplateParams::buildDateInShowingTimezone($utcStartDateTime, $shiftPlan->getUserId(), $this->currentUser->getId());
				if ($dateTimeStart)
				{
					$this->shiftPlansByUserShiftDate[$shiftPlan->getUserId()][$shiftPlan->getShiftId()][$dateTimeStart->format($this->dateTimeFormat)] = $shiftPlan;
				}
			}
		}
	}

	/**
	 * @return Grid
	 */
	protected function getGrid()
	{
		if ($this->grid === null)
		{
			$scheduleReportPeriod = null;
			if ($this->scheduleCollection->count() === 1)
			{
				$scheduleReportPeriod = $this->scheduleCollection->getFirst()->getReportPeriod();
			}
			$this->grid = Grid::getInstance(
				$this->arResult['GRID_ID'],
				array_merge($this->arResult['GRID_OPTIONS'], [
					'currentUser' => $this->currentUser,
					'weekStart' => $this->arResult['weekStart'],
					'dateTimeFormat' => $this->dateTimeFormat,
					'isShiftplan' => $this->arResult['IS_SHIFTPLAN'],
					'shiftplanScheduleId' => (int)$this->arResult['SCHEDULE_ID'],
					'hasAccessToOtherWorktime' => $this->arResult['hasAccessToOtherWorktime'],
					'scheduleReportPeriod' => $scheduleReportPeriod,
					'showAddShiftPlanBtn' => $this->arResult['SHOW_ADD_SHIFT_PLAN_BTN'],
					'drawAbsenceTitle' => $this->arResult['DRAW_ABSENCE_TITLE'] === null ? true : $this->arResult['DRAW_ABSENCE_TITLE'],
				])
			);
		}

		return $this->grid;
	}

	private function makeUrls()
	{
		$urlManager = $this->dependencyManager->getUrlManager();
		if ($this->arResult['SHOW_ADD_SCHEDULE_BTN'])
		{
			$this->arResult['ADD_SCHEDULE_LINK'] = $urlManager
				->getUriTo(Timeman\TimemanUrlManager::URI_SCHEDULE_CREATE);
		}
		$this->arResult['URLS']['SCHEDULE_EDIT'] = $urlManager->getUriTo('scheduleUpdate', ['SCHEDULE_ID' => $this->arResult['SCHEDULE_ID']]);
		$this->arResult['URLS']['SHIFT_CREATE'] = $urlManager->getUriTo('scheduleCreate');
		$baseUri = (new Main\Web\Uri($this->getRequest()->getRequestedPage()))->addParams(['apply_filter' => 'Y']);
		if ($this->getRequest()->get('IFRAME') === 'Y')
		{
			$baseUri->addParams(['IFRAME' => $this->getRequest()->get('IFRAME')]);
		}
		$nextPeriod = clone $baseUri;
		$nextPeriod->addParams(['REPORT_PERIOD_datesel' => 'RANGE'])
			->addParams(['REPORT_PERIOD_from' => reset($this->getGrid()->getPeriodNext())->toString()])
			->addParams(['REPORT_PERIOD_to' => end($this->getGrid()->getPeriodNext())->toString()]);
		$this->arResult['URLS']['PERIOD_NEXT_PARTS'] = [
			'REPORT_PERIOD_datesel' => 'RANGE',
			'REPORT_PERIOD_from' => reset($this->getGrid()->getPeriodNext())->toString(),
			'REPORT_PERIOD_to' => end($this->getGrid()->getPeriodNext())->toString(),
		];
		$prevPeriod = clone $baseUri;
		$prevPeriod->addParams(['REPORT_PERIOD_datesel' => 'RANGE'])
			->addParams(['REPORT_PERIOD_from' => reset($this->getGrid()->getPeriodPrev())->toString()])
			->addParams(['REPORT_PERIOD_to' => end($this->getGrid()->getPeriodPrev())->toString()]);
		$this->arResult['URLS']['PERIOD_PREV_PARTS'] = [
			'REPORT_PERIOD_datesel' => 'RANGE',
			'REPORT_PERIOD_from' => reset($this->getGrid()->getPeriodPrev())->toString(),
			'REPORT_PERIOD_to' => end($this->getGrid()->getPeriodPrev())->toString(),
		];

		$this->arResult['URLS']['PERIOD_PREV'] = $prevPeriod;
		$this->arResult['URLS']['PERIOD_NEXT'] = $nextPeriod;
		try
		{
			$todayStartFrom = reset(Ranges::getRange($this->getGrid()->getCurrentPeriodType(), new Main\Type\DateTime()));
			if ($todayStartFrom)
			{
				$todayStartFrom = \Bitrix\Main\Type\Date::createFromPhp($todayStartFrom)->toString();
			}
			$todayStartTo = end(Ranges::getRange($this->getGrid()->getCurrentPeriodType(), new Main\Type\DateTime()));
			if ($todayStartTo)
			{
				$todayStartTo = \Bitrix\Main\Type\Date::createFromPhp($todayStartTo)->toString();
			}
		}
		catch (\Exception $exc)
		{
			$curWeek = Ranges::getMonthRange(new \DateTime());
			$todayStartFrom = \Bitrix\Main\Type\Date::createFromPhp(reset($curWeek))->toString();
			$todayStartTo = \Bitrix\Main\Type\Date::createFromPhp(end($curWeek))->toString();
		}
		$this->arResult['URLS']['PERIOD_TODAY'] = clone $baseUri;
		$this->arResult['URLS']['PERIOD_TODAY']
			->addParams(['REPORT_PERIOD_datesel' => 'RANGE'])
			->addParams(['REPORT_PERIOD_from' => $todayStartFrom])
			->addParams(['REPORT_PERIOD_to' => $todayStartTo]);
		$this->arResult['URLS']['PERIOD_TODAY_PARTS'] = [
			'REPORT_PERIOD_datesel' => 'RANGE',
			'REPORT_PERIOD_from' => $todayStartFrom,
			'REPORT_PERIOD_to' => $todayStartTo,
		];
	}

	private function initViewResult()
	{
		$this->arResult['currentUserId'] = Main\Engine\CurrentUser::get()->getId();
		$this->currentUser = $this->buildUsersQuery()
			->where('ID', $this->arResult['currentUserId'])
			->exec()
			->fetchObject();
		$this->initFromParams('ADD_SHIFT_LINK', '');
		$this->initFromParams('IS_SHIFTPLAN', false);
		$this->arResult['SHOW_ADD_SHIFT_PLAN_BTN'] = false;
		$this->arResult['SHOW_DELETE_SHIFT_PLAN_BTN'] = false;
		if ($this->arResult['IS_SHIFTPLAN'])
		{
			$this->arResult['SHOW_ADD_SHIFT_PLAN_BTN'] = true && $this->userPermissionsManager->canUpdateShiftPlans();
			$this->arResult['SHOW_DELETE_SHIFT_PLAN_BTN'] = true && $this->userPermissionsManager->canUpdateShiftPlans();
		}

		$this->initFromParams('GRID_ID', $this->gridId . ($this->arResult['isSlider'] ? '_slider' : ''));
		$this->initFromParams('GRID_OPTIONS', []);
		$this->initFromParams('DRAW_DEPARTMENT_SEPARATOR', true);
		$this->initFromParams('PARTIAL', false);
		$this->initFromParams('TODAY_POSITIONED_LEFT', false);
		$this->initFromParams('PARTIAL_ITEM', null);
		$this->initFromParams('IS_SLIDER', false);
		$this->initFromParams('SHIFT_PLAN_FORM_NAME', 'ShiftPlanForm');
		$this->initFromParams('SHOW_PRINT_BTN', false);
		$this->initFromParams('SHOW_ADD_SHIFT_BTN', false);
		$this->initFromParams('SHOW_DELETE_USER_BTN', false);
		$this->initFromParams('SHOW_ADD_SCHEDULE_BTN', false);
		$this->arResult['DRAW_ABSENCE_TITLE'] = true;
		if ($this->getRequest()->get('drawAbsenceTitle') !== null)
		{
			$this->arResult['DRAW_ABSENCE_TITLE'] = $this->getRequest()->get('drawAbsenceTitle') !== 'N';
		}
		$useEmployeesTimezone = Application::getInstance()->getContext()->getRequest()->getCookieRaw('useEmployeesTimezone');
		if ($useEmployeesTimezone === null)
		{
			$useEmployeesTimezone = $this->getRequest()->get('useEmployeesTimezone'); // rest ajax request, lost cookies
			if ($useEmployeesTimezone === 'true' || $useEmployeesTimezone === '1')
			{
				$useEmployeesTimezone = 'Y';
			}
		}
		$this->arResult['showDatesInCurrentUserTimezone'] = $useEmployeesTimezone !== 'Y';
		TemplateParams::setShowDatesInCurrentUserTimezone($this->arResult['showDatesInCurrentUserTimezone']);

		TemplateParams::initCurrentUserPermissionsManager($this->userPermissionsManager);
		$currentUserId = (int)$this->arResult['currentUserId'];
		$this->arResult['hasAccessToOtherWorktime'] = $this->userPermissionsManager->canReadWorktimeAll() ||
													  !empty(array_filter($this->userPermissionsManager->getUserIdsAccessibleToRead(),
														  function ($item) use ($currentUserId) {
															  return $currentUserId !== $item;
														  })
													  );
		$this->arResult['recordShowUtcOffset'] = TimeHelper::getInstance()->getUserUtcOffset($this->arResult['currentUserId']);
		$this->arResult['TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH'] = Loc::getMessage('TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH');
		if (empty($this->arResult['TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH']))
		{
			$this->arResult['TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH'] = 'j F';
		}
		$this->arResult['nowDate'] = TimeHelper::getInstance()->getUserDateTimeNow($this->arResult['currentUserId'])->format($this->dateTimeFormat);
		$this->arResult['weekStart'] = 1;
		$this->arResult['addScheduleLink'] = $this->dependencyManager->getUrlManager()->getUriTo(TimemanUrlManager::URI_SCHEDULE_CREATE);
		if ($this->arResult['IS_SHIFTPLAN'] && $this->arResult['ADD_SHIFT_LINK'])
		{
			$this->arResult['addShiftLink'] = $this->arResult['ADD_SHIFT_LINK'];
		}
		$this->arResult['canManageWorktimeAll'] = $this->userPermissionsManager->canManageWorktimeAll();
		$this->arResult['canReadSchedules'] = $this->userPermissionsManager->canReadSchedules();
		$this->arResult['canDeleteSchedules'] = $this->userPermissionsManager->canDeleteSchedules();
		$this->arResult['canUpdateSchedules'] = $this->userPermissionsManager->canUpdateSchedules();
		$this->arResult['canUpdateAllShiftplans'] = $this->userPermissionsManager->canUpdateShiftPlans();
		$this->arResult['canUpdateShiftplan'] = false;
		if ($this->arResult['SCHEDULE_ID'] > 0)
		{
			$this->arResult['canUpdateShiftplan'] = $this->userPermissionsManager->canUpdateShiftPlan($this->arResult['SCHEDULE_ID']);
		}
		$this->arResult['showUserWorktimeSettings'] = !$this->arResult['IS_SHIFTPLAN'];
		if (\Bitrix\Main\Application::getInstance()->getContext()->getCulture())
		{
			$this->arResult['weekStart'] = \Bitrix\Main\Application::getInstance()->getContext()->getCulture()->getWeekStart();
		}
		$this->arResult['canManageSettings'] = count($this->userPermissionsManager->getUserIdsAccessibleToRead()) > 1 || $this->userPermissionsManager->canReadWorktimeAll();
		$this->arResult['canReadSettings'] = $this->userPermissionsManager->canManageWorktime();
		$this->arResult['departmentFilterUserIds'] = [];
		$this->arResult['baseDepartmentId'] = $this->departmentRepository->getBaseDepartmentId();
		$this->arResult['nowTime'] = time();
		$this->scheduleCollection = new Timeman\Model\Schedule\ScheduleCollection();
		$this->initCookieOptions();
	}


	private function addFilterPlansByDates($query)
	{
		$utcFrom = TimeHelper::getInstance()->createDateTimeFromFormat('U', $this->getDateTimeFrom()->getTimestamp() - Timeman\Helper\TimeDictionary::SECONDS_PER_DAY, 0);
		$utcTo = TimeHelper::getInstance()->createDateTimeFromFormat('U', $this->getDateTimeTo()->getTimestamp() + Timeman\Helper\TimeDictionary::SECONDS_PER_DAY, 0);
		$query->where('DATE_ASSIGNED', '>=', new Date($utcFrom->format(ShiftPlanTable::DATE_FORMAT), ShiftPlanTable::DATE_FORMAT));
		$query->where('DATE_ASSIGNED', '<=', new Date($utcTo->format(ShiftPlanTable::DATE_FORMAT), ShiftPlanTable::DATE_FORMAT));
	}

	private function addFilterRecordsByDates($query)
	{
		/** @var Main\ORM\Query\Query $query */
		$query->where('RECORDED_START_TIMESTAMP', '>=', $this->getDateTimeFrom() ? $this->getDateTimeFrom()->format('U') : -1);
		$query->where('RECORDED_START_TIMESTAMP', '<=', $this->getDateTimeTo() ? $this->getDateTimeTo()->format('U') : -1);
	}

	private function buildUserToDepartmentsMap()
	{
		$baseDepId = $this->departmentRepository->getBaseDepartmentId();
		if ($this->arResult['SCHEDULE_ID'] > 0)
		{
			$schedule = $this->scheduleCollection->getByPrimary($this->arResult['SCHEDULE_ID']);
			if ($schedule->getIsForAllUsers())
			{
				$schedule->obtainDepartmentAssignments()->add(ScheduleDepartment::create($schedule->getId(), $baseDepId));
			}
			$userToDepartmentsMap = DependencyManager::getInstance()
				->getScheduleProvider()
				->buildUserToDepartmentsMapByAssignments($schedule->obtainUserAssignments(), $schedule->obtainDepartmentAssignments());
		}
		else
		{
			$userToDepartmentsMap = DependencyManager::getInstance()
				->getScheduleProvider()
				->buildUserToDepartmentsMapByAssignments(
					[],
					[
						ScheduleDepartment::create(0, $baseDepId),
					]
				);
		}
		return $userToDepartmentsMap;
	}

	private function buildDepartmentsToUsersMap()
	{
		$userToDepartmentsMap = $this->buildUserToDepartmentsMap();

		$departmentToUsersMap = [];
		foreach ($userToDepartmentsMap as $userCode => $departmentsCodes)
		{
			foreach ($departmentsCodes as $departmentCode)
			{
				if (!array_key_exists($departmentCode, $departmentToUsersMap))
				{
					$departmentToUsersMap[$departmentCode] = [];
				}
				$departmentToUsersMap[$departmentCode][] = $userCode;
			}
		}
		return $departmentToUsersMap;
	}

	private function applyFiltersToUserIds(&$departmentsToUsersMap)
	{
		if ($this->getRequest()->get('USERS') && EntityCodesHelper::isUser($this->getRequest()->get('USERS')))
		{
			$this->filterUsersByCodes($departmentsToUsersMap, [$this->getRequest()->get('USERS')]);
		}

		if ($this->getGrid()->isUserFilterApplied())
		{
			$this->filterUsersByCodes($departmentsToUsersMap, $this->getGrid()->getUserCodes());
		}

		if ($this->getGrid()->isDepartmentFilterApplied())
		{
			$departmentCodesToShow = $this->getGrid()->getDepartmentCodes();
			foreach ($departmentsToUsersMap as $departmentCode => $userCodes)
			{
				foreach ($userCodes as $userCodeIndex => $userCode)
				{
					$id = EntityCodesHelper::getUserId($userCode);
					$allUserDepartmentsCodes = EntityCodesHelper::buildDepartmentCodes(
						$this->departmentRepository->getAllUserDepartmentIds($id)
					);
					$subDepCodes = EntityCodesHelper::buildDepartmentCodes(\CIntranetUtils::getSubordinateDepartments($id));
					$allUserDepartmentsCodes = array_merge($allUserDepartmentsCodes, $subDepCodes);
					if (empty(array_intersect($departmentCodesToShow, $allUserDepartmentsCodes)))
					{
						unset($departmentsToUsersMap[$departmentCode][$userCodeIndex]);
					}
				}
			}
			$departmentsToUsersMap = array_filter($departmentsToUsersMap);
		}

		if ($this->getGrid()->isShowUsersWithRecordsOnly() || $this->getGrid()->isFilterByApprovedApplied())
		{
			$query = WorktimeRecordTable::query()
				->registerRuntimeField(new ExpressionField('D_USER_ID', 'DISTINCT USER_ID'))
				->addSelect('D_USER_ID');
			$this->addFilterRecordsByDates($query);
			if (in_array($this->getGrid()->getFilterByApproved(), ['Y', 'N'], true))
			{
				$query->where('APPROVED', $this->getGrid()->getFilterByApproved() !== 'Y');
			}
			$userIdsWithRecords = array_column($query->exec()->fetchAll(), 'D_USER_ID');
			$this->filterUsersByCodes($departmentsToUsersMap, EntityCodesHelper::buildUserCodes($userIdsWithRecords));
		}

		if (!empty($this->getGrid()->getFilterFindText()))
		{
			$filterUser = \Bitrix\Main\UserUtils::getUserSearchFilter([
				'FIND' => $this->getGrid()->getFilterFindText(),
			]);

			$userIds = Timeman\Model\User\UserTable::query()
				->addSelect('ID')
				->setFilter($filterUser)
				->exec()
				->fetchAll();
			$this->filterUsersByCodes($departmentsToUsersMap, EntityCodesHelper::buildUserCodes(array_column($userIds, 'ID')));
		}
		if ($this->getGrid()->showWithShiftPlansOnly())
		{
			$plansQuery = $this->dependencyManager
				->getShiftPlanRepository()
				->getActivePlansQuery()
				->registerRuntimeField(new ExpressionField('D_USER_ID', 'DISTINCT USER_ID'))
				->addSelect('D_USER_ID');
			$this->addFilterPlansByDates($plansQuery);
			if ($this->arResult['IS_SHIFTPLAN'])
			{
				$plansQuery->whereIn('SHIFT.SCHEDULE.ID', $this->arResult['SCHEDULE_ID']);
			}
			$userIdsWithPlans = array_column($plansQuery->exec()->fetchAll(), 'D_USER_ID');

			$this->filterUsersByCodes($departmentsToUsersMap, EntityCodesHelper::buildUserCodes($userIdsWithPlans));
		}

		if ($this->getGrid()->isSchedulesFilterApplied())
		{
			$userAssignments = Timeman\Model\Schedule\Assignment\User\ScheduleUserTable::query()
				->addSelect('*')
				->whereIn('SCHEDULE_ID', $this->getGrid()->getFilteredSchedulesIds())
				->exec()
				->fetchCollection();
			$userAssignmentsByScheduleId = [];
			foreach ($userAssignments as $userAssignment)
			{
				/** @var Timeman\Model\Schedule\Assignment\User\ScheduleUser $userAssignment */
				$userAssignmentsByScheduleId[$userAssignment->getScheduleId()][] = $userAssignment;
			}
			/** @var Timeman\Model\Schedule\ScheduleCollection $schedulesForAllUsersCollection */
			$schedulesForAllUsersCollection = $this->dependencyManager->getScheduleRepository()->getActiveSchedulesQuery()
				->addSelect('ID')
				->where('IS_FOR_ALL_USERS', true)
				->whereIn('ID', $this->getGrid()->getFilteredSchedulesIds())
				->exec()
				->fetchCollection();
			$departmentAssignments = Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable::query()
				->addSelect('*')
				->whereIn('SCHEDULE_ID', $this->getGrid()->getFilteredSchedulesIds())
				->exec()
				->fetchCollection();
			$departmentAssignmentsByScheduleId = [];
			foreach ($departmentAssignments as $departmentAssignment)
			{
				/** @var ScheduleDepartment $departmentAssignment */
				$departmentAssignmentsByScheduleId[$departmentAssignment->getScheduleId()][] = $departmentAssignment;
			}
			foreach ($this->getGrid()->getFilteredSchedulesIds() as $scheduleId)
			{
				if ($schedulesForAllUsersCollection->getByPrimary($scheduleId))
				{
					$departmentAssignmentsByScheduleId[$scheduleId][] = (new ScheduleDepartment(false))
						->setDepartmentId(DependencyManager::getInstance()->getDepartmentRepository()->getBaseDepartmentId())
						->setScheduleId($scheduleId)
						->setIsIncluded();
				}
			}
			$activeUsers = [];
			foreach ($this->getGrid()->getFilteredSchedulesIds() as $scheduleId)
			{
				$map = DependencyManager::getInstance()
					->getScheduleProvider()
					->buildUserToDepartmentsMapByAssignments(
						(array)$userAssignmentsByScheduleId[$scheduleId],
						(array)$departmentAssignmentsByScheduleId[$scheduleId]
					);
				$activeUsers = array_merge(
					$activeUsers,
					EntityCodesHelper::extractUserIdsFromEntityCodes(array_keys($map))
				);
			}

			$this->filterUsersByCodes($departmentsToUsersMap, EntityCodesHelper::buildUserCodes($activeUsers));
		}
	}

	private function applyAccessControlToUserIds(&$departmentsToUsersMap)
	{
		if ($this->arResult['IS_SHIFTPLAN'])
		{
			$scheduleId = $this->arResult['SCHEDULE_ID'];
			if ($scheduleId > 0 && $this->scheduleCollection->getByPrimary($scheduleId)
				&& $this->userPermissionsManager->canReadShiftPlan($scheduleId))
			{
				return;
			}
		}
		$userCodesAccessibleToRead = $this->userPermissionsManager->getUserCodesAccessibleToRead();
		if (in_array(EntityCodesHelper::getAllUsersCode(), $userCodesAccessibleToRead, true))
		{
			return;
		}
		$userCodesAccessibleToRead[] = EntityCodesHelper::buildUserCode($this->arResult['currentUserId']);
		foreach ($departmentsToUsersMap as $departmentCode => $userCodes)
		{
			foreach ($userCodes as $userCodeIndex => $userCode)
			{
				if (!in_array($userCode, $userCodesAccessibleToRead, true))
				{
					unset($departmentsToUsersMap[$departmentCode][$userCodeIndex]);
				}
			}
		}

		$departmentsToUsersMap = array_filter($departmentsToUsersMap);
	}

	private function setTotalCount($departmentsToUsersMap)
	{
		$this->arResult['TOTAL_USERS_COUNT'] = 0;
		foreach ($departmentsToUsersMap as $userCodes)
		{
			$this->arResult['TOTAL_USERS_COUNT'] = $this->arResult['TOTAL_USERS_COUNT'] + count($userCodes);
		}
	}

	private function setLimitOffset(&$departmentsToUsersMap)
	{
		$limit = $this->getGrid()->getNavigation()->getLimit();
		$currentPage = $this->getGrid()->getNavigation()->getCurrentPage();
		$expectedSkipCount = $currentPage > 0 ? $limit * ($currentPage - 1) : 0;
		$actualSkipCount = 0;
		$cnt = 0;
		foreach ($departmentsToUsersMap as $depCode => $userCodes)
		{
			foreach ($userCodes as $userCodeIndex => $userCode)
			{
				if ($actualSkipCount < $expectedSkipCount)
				{
					$actualSkipCount++;
					unset($departmentsToUsersMap[$depCode][$userCodeIndex]);
					continue;
				}
				$cnt++;
				if ($cnt > $limit)
				{
					unset($departmentsToUsersMap[$depCode][$userCodeIndex]);
				}
			}
		}
		$departmentsToUsersMap = array_filter($departmentsToUsersMap);
	}

	private function fillDepartmentUsersData($departmentsToUsersMap)
	{
		$sectionUrl = '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#';
		$depIds = EntityCodesHelper::extractDepartmentIdsFromEntityCodes(array_keys($departmentsToUsersMap));
		$result = [];
		$heads = [];
		$obReport = new \CTimeManAdminReport([]);
		$departmentsDataRaw = $obReport->getDepartmentsData($depIds);
		foreach ($departmentsToUsersMap as $depCode => $departmentsToUsersMapItem)
		{
			$depId = EntityCodesHelper::getDepartmentId($depCode);
			if (isset($departmentsDataRaw[$depId]))
			{
				$heads[$depId] = $departmentsDataRaw[$depId]['UF_HEAD'];
				$result[$depId]['ID'] = $depId;
				$result[$depId]['CHAIN'] = $departmentsDataRaw[$depId]['CHAIN'];
				$result[$depId]['TOP_SECTION'] = $departmentsDataRaw[$depId]['TOP_SECTION'];
				$result[$depId]['NAME'] = $departmentsDataRaw[$depId]['NAME'];
				$result[$depId]['URL'] = str_replace('#ID#', $depId, $sectionUrl);
				$result[$depId]['USERS'] = [];
			}
		}

		$userIds = $this->extractUserIds($departmentsToUsersMap);

		if (!empty($userIds))
		{
			$users = $this->buildUsersQuery()
				->whereIn('ID', array_unique($userIds))
				->where('USER_TYPE_IS_EMPLOYEE', true)
				->exec()
				->fetchCollection();
			foreach ($result as $departmentDataIndex => $departmentData)
			{
				foreach ($departmentsToUsersMap as $departmentMapCode => $userCodes)
				{
					if ($departmentMapCode === EntityCodesHelper::buildDepartmentCode($departmentData['ID']))
					{
						foreach ($userCodes as $userCode)
						{
							if ($user = $users->getByPrimary(EntityCodesHelper::getUserId($userCode)))
							{
								$user->defineIsHeadOfDepartment((int)$user->getId() === (int)$heads[$departmentData['ID']]);
								$result[$departmentDataIndex]['USERS'][] = [
									'ID' => $user->getId(),
									'HEAD' => $user->obtainIsHeadOfDepartment(),
									'NAME' => $user->buildFormattedName(),
								];
								$this->usersCollection->add($user);
							}
						}
					}
				}
				if (count($result[$departmentDataIndex]['USERS']) > 1)
				{
					\Bitrix\Main\Type\Collection::sortByColumn(
						$result[$departmentDataIndex]['USERS'],
						['HEAD' => SORT_DESC, 'NAME' => SORT_ASC]
					);
				}
			}
		}
		return $result;
	}

	private function extractUserIds($departmentsToUsersMap)
	{
		$userIds = [];
		foreach ($departmentsToUsersMap as $userCodes)
		{
			foreach ($userCodes as $userCode)
			{
				$userIds[EntityCodesHelper::getUserId($userCode)] = true;
			}
		}
		return array_keys($userIds);
	}

	private function sortUserIds($departmentsToUsersMap)
	{
		$result = [];
		$departmentsData = $this->departmentRepository->getAllData();
		$departmentsIdsToShow = EntityCodesHelper::extractDepartmentIdsFromEntityCodes(array_keys($departmentsToUsersMap));
		if (!empty($departmentsIdsToShow) && !empty($departmentsData))
		{
			foreach ($departmentsData as $departmentItem)
			{
				$code = EntityCodesHelper::buildDepartmentCode($departmentItem['ID']);
				if (isset($departmentsToUsersMap[$code]))
				{
					$result[$code] = $departmentsToUsersMap[$code];
				}
			}
		}
		return $result;
	}

	private function filterUsersByCodes(&$departmentsToUsersMap, $userKeepCodes)
	{
		foreach ($departmentsToUsersMap as $departmentCode => $userCodes)
		{
			foreach ($userCodes as $userCodeIndex => $userCode)
			{
				if (!in_array($userCode, $userKeepCodes, true))
				{
					unset($departmentsToUsersMap[$departmentCode][$userCodeIndex]);
				}
			}
		}
		$departmentsToUsersMap = array_filter($departmentsToUsersMap);
	}

	private function buildParamsForPartialView()
	{
		if (!($this->arParams['USER_ID'] > 0) || !($this->arParams['DRAWING_TIMESTAMP'] > 0))
		{
			return [];
		}
		$drawingDate = TemplateParams::buildDateInShowingTimezone($this->arParams['DRAWING_TIMESTAMP'], $this->arParams['USER_ID'], $this->currentUser->getId());
		$drawingDate->setTime(0, 0, 0);
		$drawingDate = Main\Type\DateTime::createFromPhp($drawingDate);
		$this->arResult['drawingDate'] = $drawingDate;
		$this->dateTimeFrom = $drawingDate;
		$this->dateTimeTo = clone $drawingDate;
		$this->dateTimeTo->setTime(23, 59, 59);

		$this->fillScheduleRecordsPlans([$this->arParams['USER_ID']]);

		$user = $this->dependencyManager->getScheduleRepository()
			->getUsersBaseQuery(true)
			->addSelect('PERSONAL_GENDER')
			->where('ID', $this->arParams['USER_ID'])
			->exec()
			->fetchObject();
		$records = [];
		if (!empty($this->recordsByUsersDates))
		{
			$records = reset($this->recordsByUsersDates);
		}
		$sortedTemplateParamsList = $this->getGrid()->buildTemplateParamsForDayCell(
			$this->scheduleCollection,
			$drawingDate,
			$drawingDate->format($this->dateTimeFormat),
			!empty($records) ? $records[$drawingDate->format($this->dateTimeFormat)] : [],
			$this->shiftPlansByUserShiftDate,
			$user,
			$this->getGrid()->findAbsenceData([$this->arParams['USER_ID']])
		);
		$this->arResult['USER_ID'] = $this->arParams['USER_ID'];
		return $sortedTemplateParamsList;
	}

	public static function getUserOffset($userData)
	{
		// this is copy of \CTimeZone::GetOffset
		// but without select on every call
		// delete this method when \CTimeZone::GetOffset will be able to work with userIds list

		if (!\CTimeZone::optionEnabled())
		{
			return 0;
		}

		try
		{
			$localTime = new \DateTime();
			$localOffset = $localTime->getOffset();
			$userOffset = $localOffset;

			$autoTimeZone = $userZone = '';
			$factOffset = 0;
			if (!empty($userData))
			{
				$autoTimeZone = trim($userData["AUTO_TIME_ZONE"]);
				$userZone = $userData["TIME_ZONE"];
				$factOffset = intval($userData["TIME_ZONE_OFFSET"]);
			}

			if ($autoTimeZone == "N")
			{
				$userTime = ($userZone <> "" ? new \DateTime(null, new \DateTimeZone($userZone)) : $localTime);
				$userOffset = $userTime->getOffset();
			}
			else
			{
				if (\CTimeZone::isAutoTimeZone($autoTimeZone))
				{
					return $factOffset;
				}
				else
				{
					$serverZone = \COption::GetOptionString("main", "default_time_zone", "");
					$serverTime = ($serverZone <> "" ? new \DateTime(null, new \DateTimeZone($serverZone)) : $localTime);
					$userOffset = $serverTime->getOffset();
				}
			}
		}
		catch (\Exception $e)
		{
			return 0;
		}
		return $userOffset - $localOffset;
	}

	private function setTimezoneToggleAvailable(&$departmentsToUsersMap)
	{
		$this->arResult['showTimezoneToggle'] = false;
		$userIds = $this->extractUserIds($departmentsToUsersMap);
		if (empty($userIds))
		{
			return;
		}

		$offsets = [];
		$userOffsets = [];
		foreach ($userIds as $userId)
		{
			if (!$this->usersCollection->getByPrimary($userId))
			{
				continue;
			}
			$userOffsets[$userId] = $this->getUserOffset($this->usersCollection->getByPrimary($userId));
			$offsets[(string)$userOffsets[$userId]] = true;
		}
		TimeHelper::getInstance()->setTimezoneOffsets($userOffsets);
		if (count($offsets) > 1)
		{
			$this->arResult['showTimezoneToggle'] = true;
		}
		if ($this->arResult['showTimezoneToggle'] === false)
		{
			TemplateParams::setShowDatesInCurrentUserTimezone(true);
		}
	}

	private function setCookie($name, $value)
	{
		if ($this->getCookie($name) === $value)
		{
			return;
		}
		$cookie = new \Bitrix\Main\Web\Cookie($name, $value, null, false);
		$cookie->setHttpOnly(false);
		$cookie->setPath('/timeman/');
		Main\Context::getCurrent()->getResponse()->addCookie($cookie);
	}

	private function getCookie($name)
	{
		return Application::getInstance()->getContext()->getRequest()->getCookieRaw($name);
	}

	private function initCookieOptions()
	{
		$this->arResult['GRID_OPTIONS']['SHOW_VIOLATIONS_INDIVIDUAL'] = $this->getCookie('useIndividualViolationRules') === 'Y';
		$this->arResult['GRID_OPTIONS']['SHOW_START_END'] = $this->getCookie('showStartEndTime') === 'Y';
		if ($this->arResult['IS_SHIFTPLAN'])
		{
			$this->arResult['GRID_OPTIONS']['SHOW_START_END'] = true;
		}
		$this->arResult['GRID_OPTIONS']['SHOW_STATS_COLUMNS'] = $this->getCookie('showStatsColumns') === 'Y';
		if ($this->arResult['IS_SHIFTPLAN'])
		{
			$this->arResult['GRID_OPTIONS']['SHOW_STATS_COLUMNS'] = false;
		}
	}

	private function initGridOptions()
	{
		$this->arResult['IS_SHIFTPLAN_LIST_BTN_ENABLED'] = !$this->arResult['IS_SHIFTPLAN'];
		$this->arResult['HIDE_SHIFTPLAN_LIST_BTN'] = true;
		$this->arResult['gridConfigOptions'] = [];
		$this->arResult['gridConfigOptions']['showViolationsItem'] = false;
		$hasCommonViolations = false;
		$hasIndividualViolations = false;
		foreach ($this->arResult['DEPARTMENT_USERS_DATA'] as $departmentData)
		{
			foreach ((array)$departmentData['USERS_DATA_BY_DATES'] as $usersData)
			{
				foreach ($usersData as $dayParams)
				{
					foreach ($dayParams as $templateParams)
					{
						/** @var TemplateParams $templateParams */
						if (!empty($templateParams->violationsIndividual)
							|| !empty($templateParams->noticesIndividual))
						{
							$hasIndividualViolations = true;
						}
						if (!empty($templateParams->violationsCommon)
							|| !empty($templateParams->noticesCommon))
						{
							$hasCommonViolations = true;
						}
					}
				}
			}
		}
		if ($hasCommonViolations && $hasIndividualViolations)
		{
			$this->arResult['gridConfigOptions']['showViolationsItem'] = true;
		}
		elseif ($hasCommonViolations !== $hasIndividualViolations)
		{
			// we have only one type of violations - show this type and no toggle
			$this->arResult['GRID_OPTIONS']['SHOW_VIOLATIONS_INDIVIDUAL'] = $hasIndividualViolations;
		}
		$this->arResult['gridConfigOptions']['showStartEndItem'] = !$this->arResult['IS_SHIFTPLAN'];
		$this->arResult['gridConfigOptions']['showStatsItem'] = !$this->arResult['IS_SHIFTPLAN'];
		$this->arResult['gridConfigOptions']['showSchedulesItem'] = $this->userPermissionsManager->canReadSchedules();
		$this->arResult['gridConfigOptions']['schedules'] = [];

		if ($this->userPermissionsManager->canReadSchedules())
		{
			if ($this->arResult['IS_SHIFTPLAN'])
			{
				if ($this->userPermissionsManager->canUpdateSchedules())
				{
					// update current schedule
					$this->arResult['gridConfigOptions']['schedules'][] = $this->buildGridConfigScheduleItem($this->scheduleCollection->getByPrimary($this->arResult['SCHEDULE_ID']));
				}
			}
			else
			{
				$schedules = $this->dependencyManager->getScheduleRepository()->getActiveSchedulesQuery()
					->addSelect('ID')
					->addSelect('NAME')
					->addSelect('SCHEDULE_TYPE')
					->setOrder('NAME')
					->setLimit(80)
					->exec()
					->fetchCollection();
				if ($schedules->count() > 0)
				{
					foreach ($schedules as $schedule)
					{
						$this->arResult['gridConfigOptions']['schedules'][] = $this->buildGridConfigScheduleItem($schedule);
						if ($schedule->isShifted() && $this->userPermissionsManager->canReadShiftPlan($schedule->getId()))
						{
							$this->arResult['HIDE_SHIFTPLAN_LIST_BTN'] = false;
						}
					}
				}
			}
		}
		else
		{
			$userSchedules = $this->dependencyManager->getScheduleProvider()
				->findSchedulesByUserId($this->arResult['currentUserId']);
			foreach ($userSchedules as $userSchedule)
			{
				if ($userSchedule->isShifted() && $this->userPermissionsManager->canReadShiftPlan($userSchedule->getId()))
				{
					$this->arResult['HIDE_SHIFTPLAN_LIST_BTN'] = false;
					$this->arResult['gridConfigOptions']['schedules'][] = $this->buildGridConfigScheduleItem($userSchedule);
				}
			}
		}
	}

	private function buildGridConfigScheduleItem(Timeman\Model\Schedule\Schedule $schedule)
	{
		$item = [
			'id' => $schedule->getId(),
			'name' => $schedule->getName(),
			'scheduleType' => $schedule->getScheduleType(),
			'link' => $this->dependencyManager->getUrlManager()
				->getUriTo(TimemanUrlManager::URI_SCHEDULE_UPDATE, ['SCHEDULE_ID' => $schedule->getId()]),
			'canReadShiftPlan' => false,
		];
		if ($schedule->isShifted())
		{
			$item['canReadShiftPlan'] = $this->userPermissionsManager->canReadShiftPlan($schedule->getId());
			$item['shiftplanLink'] = $this->dependencyManager->getUrlManager()
				->getUriTo(TimemanUrlManager::URI_SCHEDULE_SHIFTPLAN, ['SCHEDULE_ID' => $schedule->getId()]);
		}
		return $item;
	}

	/**
	 * @return Timeman\Model\User\EO_User_Query
	 */
	private function buildUsersQuery()
	{
		return $this->dependencyManager
			->getScheduleRepository()
			->getUsersBaseQuery()
			->addSelect('PERSONAL_GENDER')
			->addSelect('AUTO_TIME_ZONE')
			->addSelect('TIME_ZONE')
			->addSelect('TIME_ZONE_OFFSET');
	}
}
