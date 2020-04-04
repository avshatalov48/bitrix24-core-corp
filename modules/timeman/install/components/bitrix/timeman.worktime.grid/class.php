<?php
namespace Bitrix\Timeman\Component\SchedulePlan;

use Bitrix\Intranet\Internals\UserToDepartmentTable;
use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use \Bitrix\Timeman;
use Bitrix\Timeman\Component\WorktimeGrid\Grid;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Component\WorktimeGrid\Ranges;
use CIntranetUtils;
use COption;
use CTimeManAdminReport;

require_once __DIR__ . '/grid.php';
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
	/**
	 * @var array
	 */
	private $accessibleToReadUsers = [];
	private $canReadAllWorktime = false;
	private $dateTimeFormat = 'd.m.Y';

	public function __construct($component = null)
	{
		global $USER;
		$this->userPermissionsManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);

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
		$this->initTemplateParams();
		$this->arResult['weekStart'] = 1;
		$this->arResult['canReadSchedules'] = $this->userPermissionsManager->canReadSchedules();
		if (\Bitrix\Main\Application::getInstance()->getContext()->getCulture())
		{
			$this->arResult['weekStart'] = \Bitrix\Main\Application::getInstance()->getContext()->getCulture()->getWeekStart();
		}
		$this->arResult['currentUserId'] = Main\Engine\CurrentUser::get()->getId();
		$currentUserId = (int)$this->arResult['currentUserId'];
		$this->arResult['showFilter'] = $this->userPermissionsManager->canReadWorktimeAll() ||
										!empty(array_filter($this->userPermissionsManager->getUserIdsAccessibleToRead(),
											function ($item) use ($currentUserId) {
												return $currentUserId !== $item;
											})
										);
		$this->arResult['recordShowUtcOffset'] = TimeHelper::getInstance()->getUserUtcOffset($this->arResult['currentUserId']);
		$this->arResult['canManageSettings'] = count($this->userPermissionsManager->getUserIdsAccessibleToRead()) > 1 || $this->userPermissionsManager->canReadWorktimeAll();
		$this->arResult['canReadSettings'] = $this->userPermissionsManager->canManageWorktime();
		$this->arResult['departmentFilterUserIds'] = [];

		if ($this->arResult['PARTIAL_ITEM'] === 'shiftCell')
		{
			Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/js_core.php');
			$this->initFromParams('SCHEDULE_ID', null);
			$this->initFromParams('DRAWING_DATE', null);
			$this->initFromParams('SHIFT_PLAN', [], 'array');
			$this->initFromParams('USER_ID', 0, 'int');
			$this->initFromParams('IS_SHIFTED_SCHEDULE', true);
			$this->initFromParams('WORKTIME_RECORD', [], 'array');
			$this->initFromParams('DATA_ATTRS', [], 'array');
			$this->initFromParams('GRID_OPTIONS', ['SHOW_USER_ABSENCES' => true], 'array');

			$users = $this->prepareUsersData([$this->arResult['USER_ID']], $addManagers = false);
			$gridDataRecord = [
				$this->arResult['USER_ID'] =>
					[
						$this->arResult['DRAWING_DATE']->format($this->dateTimeFormat) =>
							[
								$this->arResult['WORKTIME_RECORD']['ID'] =>
									[
										'record' => $this->arResult['WORKTIME_RECORD'],
										'plan' => [],
									],
							],
					],
			];
			$this->arResult['SCHEDULES'] = $this->fetchSchedules([$this->arResult['SCHEDULE_ID']]);
			$this->getGrid()->setSchedules($this->arResult['SCHEDULES']);
			$this->getGrid()->setDateTimeFrom($this->arResult['DRAWING_DATE']);
			$this->getGrid()->setDateTimeTo($this->arResult['DRAWING_DATE']);
			$this->arResult['USER_GRID_DATA'] = &$this->getGrid()->getRowsData(
				$users,
				$gridDataRecord,
				$this->findViolationRules([$this->arResult['USER_ID']]),
				[
					'SHOW_ADD_SHIFT_PLAN_BTN' => $this->arResult['SHOW_ADD_SHIFT_PLAN_BTN'],
				]
			);
			$this->arResult['WORKTIME_RECORD'] = reset(reset(reset($this->arResult['USER_GRID_DATA'])))['WORKTIME_RECORD'];
			$this->includeComponentTemplate('fixed-flex-cell');
			return;
		}


		$this->arResult['DEPARTMENTS_USERS_DATA'] = [
			'USERS' => [],
			'DEPARTMENTS' => [],
		];
		$this->canReadAllWorktime = $this->userPermissionsManager->canReadWorktimeAll();
		$this->accessibleToReadUsers = $this->userPermissionsManager->getUserIdsAccessibleToRead();

		$userIds = $this->buildUserIdsToShow();
		$users = [];

		# users
		if (!empty($userIds))
		{
			$users = $this->prepareUsersData(
				$userIds,
				!$this->getGrid()->isUserFilterApplied()
				&& !$this->getGrid()->isFilterByApprovedApplied()
				&& !$this->getGrid()->isShowUsersWithRecordsOnly()
				&& $this->getGrid()->getFilterFindText() === null
			);
		}

		$recordsDataToShow = $this->fillWithRecordsPlans();

		$grid = $this->getGrid();
		$this->arResult['NAV_OBJECT'] = $grid->getNavigation();
		$grid->getNavigation()->setRecordCount($this->arResult['TOTAL_USERS_COUNT']);
		$this->arResult['FILTER'] = $grid->getFilter();
		$grid->setSchedules($this->arResult['SCHEDULES']);
		$this->arResult['HEADERS'] = $grid->getHeaders();
		$this->arResult['DATES'] = $grid->getPeriodDates();
		$this->arResult['nowTime'] = time();
		$userIdsFromDepartmentsData = [];
		foreach ($this->arResult['DEPARTMENTS_USERS_DATA']['DEPARTMENTS'] as $depId => $depData)
		{
			foreach ((array)$depData['USERS'] as $user)
			{
				$userIdsFromDepartmentsData[$user['ID']] = true;
			}
		}
		$this->arResult['USER_GRID_DATA'] = &$grid->getRowsData(
			$users,
			$recordsDataToShow,
			$this->findViolationRules(array_keys($userIdsFromDepartmentsData)),
			['SHOW_ADD_SHIFT_PLAN_BTN' => $this->arResult['SHOW_ADD_SHIFT_PLAN_BTN'],]
		);
		if ($this->arResult['GRID_OPTIONS']['SHOW_STATS_COLUMNS'])
		{
			$this->arResult['WORKTIME_STATISTICS'] = &$grid->getWorktimeStatistics($this->arResult['USER_GRID_DATA']);
		}

		$this->makeUrls();
		$this->arResult['usersIds'] = [];
		$this->arResult['departmentsIds'] = array_keys($this->arResult['DEPARTMENTS_USERS_DATA']['DEPARTMENTS']);
		foreach ($this->arResult['DEPARTMENTS_USERS_DATA']['DEPARTMENTS'] as $departmentDataWithUsers)
		{
			$this->arResult['usersIds'] = array_merge($this->arResult['usersIds'], array_keys($departmentDataWithUsers['USERS']));
		}
		$this->arResult['DEPARTMENTS'] = [];
		if (!empty($this->arResult['departmentsIds']))
		{
			$this->arResult['DEPARTMENTS'] = $this->getDepartmentsData($this->arResult['departmentsIds']);
		}

		$this->includeComponentTemplate();
	}

	private function findViolationRules($userIds)
	{
		if (empty($this->arResult['SCHEDULES']))
		{
			return [];
		}
		$depChain = [];
		$entitiesCodes = [];
		foreach ($userIds as $userId)
		{
			$depChain[$userId] = DependencyManager::getInstance()
				->getDepartmentRepository()
				->buildUserDepartmentsPriorityTree($userId);
			foreach ($depChain[$userId] as $treeData)
			{
				$entitiesCodes = array_merge($entitiesCodes, $treeData);
			}
		}
		$entitiesCodes = array_unique($entitiesCodes);
		$sIds = [];
		foreach ($this->arResult['SCHEDULES'] as $schedule)
		{
			$sIds[] = $schedule->getId();
		}
		if (empty($entitiesCodes) || empty($sIds))
		{
			return [];
		}
		$violationRulesCollection = Timeman\Model\Schedule\Violation\ViolationRulesTable::query()
			->addSelect('*')
			->whereIn('ENTITY_CODE', $entitiesCodes)
			->whereIn('SCHEDULE_ID', $sIds)
			->exec()
			->fetchCollection();
		$violationRulesList = [];
		foreach ($violationRulesCollection as $violationRules)
		{
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

	private function getDepartmentsData($ids)
	{
		$obReport = new CTimeManAdminReport([
			'show_all' => !$this->getGrid()->isShowUsersWithRecordsOnly(),
			'ts' => null,
			'page' => $this->getGrid()->getNavigation()->getCurrentPage(),
			'amount' => $this->getGrid()->getNavigation()->getLimit(),
			'department' => $this->getGrid()->getDepartmentId(),
			'path_user' => COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/'),
			'nav_handler' => '',
		]);
		return $obReport->getDepartmentsData($ids);
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

	protected function fetchSchedules($ids)
	{
		return DependencyManager::getInstance()
			->getScheduleRepository()
			->getActiveSchedulesQuery()
			->addSelect('*')
			->addSelect('SCHEDULE_VIOLATION_RULES')
			->addSelect('DEPARTMENTS')
			->registerRuntimeField('DEPARTMENTS', (new OneToMany('DEPARTMENTS', ScheduleDepartmentTable::class, 'SCHEDULE'))->configureJoinType('LEFT'))
			->addSelect('DEPARTMENTS.DEPARTMENT_ID', 'DEPARTMENT_ID')
			->addSelect('SHIFTS.ID', 'SH_ID')
			->addSelect('SHIFTS.NAME', 'SH_NAME')
			->addSelect('SHIFTS.WORK_DAYS', 'SH_WORK_DAYS')
			->addSelect('SHIFTS.WORK_TIME_START', 'SH_WORK_TIME_START')
			->addSelect('SHIFTS.WORK_TIME_END', 'SH_WORK_TIME_END')
			->whereIn('ID', $ids)
			->addOrder('SHIFTS.WORK_TIME_START')
			->exec()
			->fetchCollection();
	}

	private function getDateTimeTo()
	{
		return $this->getGrid()->getDateTimeTo();
	}

	private function getDateTimeFrom()
	{
		return $this->getGrid()->getDateTimeFrom();
	}

	private function fetchUsersOfSchedule($schedule, $allUserIdsByFilter = [])
	{
		$options = [];

		if (!empty($allUserIdsByFilter))
		{
			$options['USER_IDS'] = $this->arResult['FILTERED_USER_IDS'];
		}
		$req = DependencyManager::getInstance()
			->getScheduleRepository()
			->buildActiveScheduleUsersQuery(
				$schedule['ID'],
				$schedule['DEPARTMENTS'],
				$options
			);
		if ($this->getGrid()->isShowUsersWithRecordsOnly())
		{
			$req->registerRuntimeField(
				(new Reference(
					'RECORD',
					WorktimeRecordTable::class,
					Join::on('this.ID', 'ref.USER_ID')
				))->configureJoinType('inner'));
			$this->addFilterRecordsByDates($req, 'RECORD.RECORDED_START_TIMESTAMP');
		}
		$res = [];
		foreach ($req->exec()->fetchAll() as $user)
		{
			$user['SCHEDULE_ID'] = $schedule['ID'];
			$res[$user['ID']] = $user;
		}
		return $res;
	}

	private function fillWithRecordsPlans()
	{
		$usersIds = [];
		$results = [];
		$scheduleIds = [];

		foreach ($this->arResult['DEPARTMENTS_USERS_DATA']['DEPARTMENTS'] as $department)
		{
			$usersIds = array_merge($usersIds, array_keys($department['USERS']));
			foreach ($department['USERS'] as $userData)
			{
				foreach ((array)$userData['RECORDS'] as $entryData)
				{
					$startDate = TimeHelper::getInstance()->createDateTimeFromFormat(
						'U',
						$entryData['RECORDED_START_TIMESTAMP'],
						$this->arResult['recordShowUtcOffset']
					);
					$results[$userData['ID']][$startDate->format($this->dateTimeFormat)][$entryData['ID']] = [
						'record' => $entryData,
						'plan' => [],
					];
					$scheduleIds[$entryData['SCHEDULE_ID']] = true;
				}
			}
		}
		$scheduleIds = array_keys($scheduleIds);
		if ($this->arResult['SCHEDULES'] === null && !empty($scheduleIds))
		{
			$this->arResult['SCHEDULES'] = $this->fetchSchedules($scheduleIds);
		}

		return $results;
	}

	/**
	 * @return Grid
	 */
	protected function getGrid()
	{
		if ($this->grid === null)
		{
			$this->grid = Grid::getInstance(
				$this->arResult['GRID_ID'],
				array_merge($this->arResult['GRID_OPTIONS'], [
					'CURRENT_USER_ID' => $this->arResult['currentUserId'],
					'WEEK_START' => $this->arResult['weekStart'],
				])
			);
		}

		return $this->grid;
	}

	public function getNormalDate($datetime, $format = null, $clone = false)
	{
		if (!($datetime instanceof \DateTime))
		{
			if (!($datetime instanceof Main\Type\Date))
			{
				$datetime = new Main\Type\DateTime($datetime, $format);
			}

			$datetime = (new \DateTime())->setTimestamp($datetime->getTimestamp());
		}
		elseif ($clone)
		{
			$datetime = clone $datetime;
		}

		return $datetime;
	}

	private function makeUrls()
	{
		if ($this->arResult['SHOW_ADD_SCHEDULE_BTN'])
		{
			$this->arResult['ADD_SCHEDULE_LINK'] = DependencyManager::getInstance()->getUrlManager()
				->getUriTo(Timeman\TimemanUrlManager::URI_SCHEDULE_CREATE);
		}
		$this->arResult['URLS']['SCHEDULE_EDIT'] = DependencyManager::getInstance()->getUrlManager()->getUriTo('scheduleUpdate', ['SCHEDULE_ID' => $this->arResult['SCHEDULE_ID']]);
		$this->arResult['URLS']['SHIFT_CREATE'] = DependencyManager::getInstance()->getUrlManager()->getUriTo('scheduleCreate');
		$baseUri = (new Main\Web\Uri($this->getRequest()->getRequestedPage()))->addParams(['apply_filter' => 'Y']);
		if ($this->arResult['GRID_OPTIONS']['SHOW_START_FINISH'] !== null)
		{
			$baseUri->addParams(['SHOW_START_FINISH' => $this->arResult['GRID_OPTIONS']['SHOW_START_FINISH'] ? 'Y' : 'N']);
		}
		if ($this->arResult['GRID_OPTIONS']['SHOW_STATS_COLUMNS'] !== null)
		{
			$baseUri->addParams(['SHOW_STATS_COLUMNS' => $this->arResult['GRID_OPTIONS']['SHOW_STATS_COLUMNS'] ? 'Y' : 'N']);
		}
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

	private function filterUserIdsAccessibleToRead($userIds)
	{
		$accessibleToReadUserIds = [];
		foreach ($userIds as $userId)
		{
			if ($this->canReadAllWorktime || in_array((int)$userId, $this->accessibleToReadUsers, true))
			{
				$accessibleToReadUserIds[] = $userId;
			}
		}
		return $accessibleToReadUserIds;
	}

	private function getUserDepartmentMapQuery($userIds, $departmentIds = [])
	{
		$userMapQuery = UserToDepartmentTable::query();
		if (empty($departmentIds) && !empty($userIds))
		{
			$userMapQuery->whereIn('USER_ID', $userIds);
		}
		elseif (empty($userIds) && !empty($departmentIds))
		{
			$userMapQuery->whereIn('DEPARTMENT_ID', $departmentIds);
		}
		elseif (!empty($userIds) && !empty($departmentIds))
		{
			$userMapQuery->where(
				Main\Entity\Query::filter()->logic('or')
					->whereIn('USER_ID', $userIds)
					->whereIn('DEPARTMENT_ID', $departmentIds)
			);
		}
		if ($this->arResult['SCHEDULE_ID'] && !empty($this->arResult['SCHEDULES']))
		{
			$schedule = $this->arResult['SCHEDULES']->getByPrimary($this->arResult['SCHEDULE_ID']);
			if ($schedule)
			{
				$userMapQuery->whereIn('USER_ID', array_merge(
					[-1],
					array_keys($this->fetchUsersOfSchedule($schedule))
				));
			}
		}
		return $userMapQuery;
	}

	private function initTemplateParams()
	{
		$this->initFromParams('SHOW_GRID_SETTINGS_BTN', false);
		$this->initFromParams('SHOW_DELETE_SHIFT_PLAN_BTN', true);
		$this->initFromParams('SHOW_SCHEDULES_LIST_BTN', false);
		$this->initFromParams('SHOW_GRID_SETTINGS_BTN', true);
		$this->initFromParams('GRID_ID', $this->gridId . ($this->arResult['isSlider'] ? '_slider' : ''));
		$this->initFromParams('GRID_OPTIONS', []);
		$this->initFromParams('DRAW_DEPARTMENT_SEPARATOR', true);
		$this->initFromParams('WRAP_CELL_IN_RECORD_LINK', false);
		$this->initFromParams('PARTIAL', false);
		$this->initFromParams('PARTIAL_ITEM', null);
		$this->initFromParams('IS_SLIDER', false);
		$this->initFromParams('SHOW_PRINT_BTN', false);
		$this->initFromParams('SHOW_EDIT_SCHEDULE_BTN', false);
		$this->initFromParams('SHOW_CREATE_SHIFT_BTN', false);
		$this->initFromParams('SHOW_DELETE_USER_BTN', false);
		$this->initFromParams('SHOW_SHIFTPLAN_LIST_BTN', false);
		$this->initFromParams('SHOW_ADD_SCHEDULE_BTN', false);
		if (!array_key_exists('SHOW_STATS_COLUMNS', $this->arResult['GRID_OPTIONS']))
		{
			$this->arResult['GRID_OPTIONS']['SHOW_STATS_COLUMNS'] = $this->getRequest()->get('SHOW_STATS_COLUMNS') === 'Y';
		}
		$this->initFromParams('SHIFT_PLAN_FORM_NAME', 'ShiftPlanForm');
		$this->initFromParams('SHOW_ADD_SHIFT_PLAN_BTN', false);
		$this->arResult['TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH'] = Loc::getMessage('TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH');
		if (empty($this->arResult['TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH']))
		{
			$this->arResult['TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_FULL_MONTH'] = 'j F';
		}
		$this->arResult['nowDate'] = TimeHelper::getInstance()->getUserDateTimeNow(Main\Engine\CurrentUser::get()->getId())->format($this->dateTimeFormat);
	}

	private function prepareUsersToShowOldWay($accessToReadUserIdsParam)
	{
		$userIdsToShow = [];
		$this->arResult['TOTAL_USERS_COUNT'] = CIntranetUtils::GetEmployeesCountForSorting(
			(int)$this->getGrid()->getDepartmentId(),
			0,
			$this->canReadAllWorktime ? false : $accessToReadUserIdsParam
		);
		$departmentFilterUserIds = \CIntranetUtils::GetEmployeesForSorting(
			$this->getGrid()->getNavigation()->getCurrentPage(),
			$this->getGrid()->getNavigation()->getLimit(),
			(int)$this->getGrid()->getDepartmentId(),
			$this->canReadAllWorktime ? false : $accessToReadUserIdsParam
		);
		$this->arResult['departmentFilterUserIds'] = $departmentFilterUserIds;
		foreach ($departmentFilterUserIds as $depId => $depUserIds)
		{
			foreach ($depUserIds as $depUserId)
			{
				$userIdsToShow[] = (int)$depUserId;
			}
		}
		return $userIdsToShow;
	}

	private function prepareUsersToShow($accessibleFilteredUserIds)
	{
		$userIdsToShow = [];
		$this->arResult['TOTAL_USERS_COUNT'] = (int)$this->arResult['TOTAL_USERS_COUNT'] + count($accessibleFilteredUserIds);

		$userOffset = $this->getGrid()->getNavigation()->getOffset() - (int)$this->arResult['TOTAL_USERS_COUNT'];
		if (!empty($accessibleFilteredUserIds))
		{
			$filteredUsers = DependencyManager::getInstance()
				->getScheduleRepository()
				->getUsersBaseQuery($idsOnly = true)
				->whereIn('ID', $accessibleFilteredUserIds)
				->where('USER_TYPE_IS_EMPLOYEE', true)
				->addOrder('ID', 'ASC')
				->setLimit($this->getGrid()->getNavigation()->getLimit() - count($userIdsToShow))
				->setOffset($userOffset > 0 ? $userOffset : 0)
				->exec()
				->fetchAll();
			$filteredUsersIdsToShow = array_map('intval', array_column($filteredUsers, 'ID'));
			$userIdsToShow = array_merge($filteredUsersIdsToShow, array_combine($userIdsToShow, $userIdsToShow));
		}
		return $userIdsToShow;
	}

	private function addToAccessUserIds($userIds)
	{
		if (!$this->userPermissionsManager->canReadWorktimeAll())
		{
			$this->accessibleToReadUsers = array_intersect($this->accessibleToReadUsers, $userIds);
		}
		else
		{
			if (!empty($this->accessibleToReadUsers))
			{
				$this->accessibleToReadUsers = array_intersect($this->accessibleToReadUsers, $userIds);
			}
			else
			{
				$this->accessibleToReadUsers = $userIds;
			}
		}
	}

	private function prepareUsersData($userIdsToShow, $addManagers = true)
	{
		$usersDepartmentsMap = $this->getUserDepartmentMapQuery($userIdsToShow)
			->addSelect('USER_ID')
			->addSelect('DEPARTMENT_ID')
			->exec()
			->fetchAll();

		$userIdsWithoutDepartments = array_diff($userIdsToShow, array_map('intval', array_unique(array_column($usersDepartmentsMap, 'USER_ID'))));
		if (!empty($userIdsWithoutDepartments))
		{
			$res = UserTable::getList([
				'select' => ['ID', 'UF_DEPARTMENT'],
				'filter' => ['=ACTIVE' => 'Y', '=ID' => array_values($userIdsWithoutDepartments)],
			]);
			while ($item = $res->fetch())
			{
				if (is_array($item['UF_DEPARTMENT']) && count($item['UF_DEPARTMENT']) > 0)
				{
					foreach ($item['UF_DEPARTMENT'] as $depId)
					{
						$usersDepartmentsMap[] = [
							'USER_ID' => $item['ID'],
							'DEPARTMENT_ID' => $depId,
						];
					}
				}
			}
		}
		$allUniqueDepartmentIds = array_unique(array_merge(
			array_keys($this->arResult['departmentFilterUserIds']),
			array_map('intval', array_unique(array_column($usersDepartmentsMap, 'DEPARTMENT_ID')))
		));
		$depManagerMap = [];
		if ($addManagers)
		{
			foreach ($allUniqueDepartmentIds as $depId)
			{
				$managerId = (int)CIntranetUtils::GetDepartmentManagerID($depId);
				if (!($managerId > 0 && $this->userPermissionsManager->canReadWorktime($managerId)))
				{
					continue;
				}
				$userExist = false;
				$userIdsToShow[] = $managerId;
				$depManagerMap[(int)$depId][] = $managerId;
				foreach ($usersDepartmentsMap as $usersDepartmentsData)
				{
					if ((int)$usersDepartmentsData['USER_ID'] === $managerId
						&& (int)$usersDepartmentsData['DEPARTMENT_ID'] === (int)$depId)
					{
						$userExist = true;
						break;
					}
				}
				if (!$userExist)
				{
					$usersDepartmentsMap[] = [
						'USER_ID' => $managerId,
						'DEPARTMENT_ID' => $depId,
					];
				}
			}
		}
		$usersQuery = DependencyManager::getInstance()
			->getScheduleRepository()
			->getUsersBaseQuery()
			->addSelect('PERSONAL_GENDER')
			->whereIn('ID', array_unique($userIdsToShow))
			->where('USER_TYPE_IS_EMPLOYEE', true);

		$users = $usersQuery->exec()->fetchAll();
		$users = array_combine(array_column($users, 'ID'), $users);

		if (!empty($users))
		{
			$records = [];
			$recordsRaw = WorktimeRecordTable::query()
				->addSelect('*')
				->whereIn('USER_ID', array_column($users, 'ID'));
			$this->addFilterRecordsByDates($recordsRaw);
			if ($this->getGrid()->isFilterByApprovedApplied())
			{
				$recordsRaw->where('APPROVED', $this->getGrid()->getFilterByApproved() !== 'Y');
			}
			$recordsRaw = $recordsRaw->exec()->fetchAll();
			foreach ($recordsRaw as $index => $record)
			{
				$records[$record['USER_ID']][$record['ID']] = $record;
			}
			foreach ($users as $index => $user)
			{
				$users[$index]['NAME'] = Timeman\Helper\UserHelper::getInstance()->getFormattedName($user);
				$users[$index]['RECORDS'] = [];
				$users[$index]['SCHEDULE_ID'] = $this->arResult['SCHEDULE_ID'];
				if (isset($records[$user['ID']]))
				{
					$users[$index]['RECORDS'] = $records[$user['ID']];
				}
			}

			if (!empty($usersDepartmentsMap))
			{
				$sectionUrl = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
				$departmentsDataRaw = CIntranetUtils::GetDepartmentsData($allUniqueDepartmentIds);
				$departmentsData = [];
				foreach ($departmentsDataRaw as $depId => $departmentsDatum)
				{
					$departmentsData[$depId]['ID'] = $depId;
					$departmentsData[$depId]['CHAIN'] = [];
					$departmentsData[$depId]['NAME'] = $departmentsDatum;
					$departmentsData[$depId]['URL'] = str_replace('#ID#', $depId, $sectionUrl);
				}
				foreach ($departmentsData as $depId => $departmentDatum)
				{
					$departmentDatum['USERS'] = [];
					foreach ($usersDepartmentsMap as $usersDepartmentData)
					{
						if ($usersDepartmentData['DEPARTMENT_ID'] == $depId)
						{
							$userId = $usersDepartmentData['USER_ID'];
							if (!empty($users[$userId]))
							{
								$departmentDatum['USERS'][$userId] = $users[$userId];
							}
						}
					}
					$currentManagerIds = (empty($depManagerMap[$depId]) ? [] : $depManagerMap[$depId]);
					uksort($departmentDatum['USERS'], function ($a, $b) use ($currentManagerIds) {
						if (in_array($a, $currentManagerIds, true))
						{
							return -1;
						}
						if (in_array($b, $currentManagerIds, true))
						{
							return 1;
						}
						return 0;
					});
					$this->arResult['DEPARTMENTS_USERS_DATA']['DEPARTMENTS'][$depId] = $departmentDatum;
				}
			}
		}
		return $users;
	}

	private function buildUserIdsToShow()
	{
		$userIdsToShow = [];
		if ($this->getGrid()->getFilterFindText() !== null)
		{
			$nameLikeUserIds = array_column(
				Main\UserTable::query()
					->addSelect('ID')
					->where(
						Main\ORM\Query\Query::filter()->logic('or')
							->whereLike('NAME', '%' . $this->getGrid()->getFilterFindText() . '%')
							->whereLike('LAST_NAME', '%' . $this->getGrid()->getFilterFindText() . '%')
					)
					->exec()
					->fetchAll(),
				'ID'
			);
			$nameLikeUserIds = array_map('intval', $nameLikeUserIds);
			$this->addToAccessUserIds($nameLikeUserIds);
			$this->canReadAllWorktime = false;
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
			if (!empty($this->accessibleToReadUsers))
			{
				$query->whereIn('USER_ID', $this->accessibleToReadUsers);
			}
			$activeUserIds = array_column($query->exec()->fetchAll(), 'D_USER_ID');
			$activeUserIds = array_map('intval', $activeUserIds);
			$this->addToAccessUserIds($activeUserIds);
			$this->canReadAllWorktime = false;
		}

		$filteredUserIds = $this->getGrid()->getUserIds();


		if ($this->getGrid()->getDepartmentId() !== null || empty($filteredUserIds))
		{
			$useOldSearch = true;

			if ($this->getGrid()->getDepartmentId() > 0 && !empty($filteredUserIds))
			{
				$this->addToAccessUserIds($filteredUserIds);
				$this->canReadAllWorktime = false;
				$repo = DependencyManager::getInstance()->getDepartmentRepository();
				foreach ($this->accessibleToReadUsers as $index => $accessToReadUserId)
				{
					$filteredUserDepartments = $repo->getAllUserDepartmentIds($accessToReadUserId);
					if (!in_array($this->getGrid()->getDepartmentId(), $filteredUserDepartments, true))
					{
						$useOldSearch = false;
						unset($this->accessibleToReadUsers[$index]);
					}
				}
			}
			if ($useOldSearch)
			{
				$userIdsToShow = $this->prepareUsersToShowOldWay($this->accessibleToReadUsers);
			}
			else
			{
				$userIdsToShow = $this->prepareUsersToShow($this->accessibleToReadUsers);
			}
		}
		elseif (!empty($filteredUserIds))
		{
			$userIdsToShow = $this->prepareUsersToShow($this->filterUserIdsAccessibleToRead($filteredUserIds));
		}
		return $userIdsToShow;
	}

	/**
	 * @param Main\ORM\Query\Query $query
	 * @param null $recordedStartColumnName
	 */
	public function addFilterRecordsByDates($query, $recordedStartColumnName = null)
	{
		$recordedStartColumnName = $recordedStartColumnName === null ? 'RECORDED_START_TIMESTAMP' : $recordedStartColumnName;
		$query
			->where($recordedStartColumnName, '>=', $this->getDateTimeFrom() ? $this->getDateTimeFrom()->format('U') : -1)
			->where($recordedStartColumnName, '<=', $this->getDateTimeTo() ? $this->getDateTimeTo()->format('U') : -1);
	}
}
