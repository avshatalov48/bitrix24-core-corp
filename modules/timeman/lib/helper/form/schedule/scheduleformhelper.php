<?php
namespace Bitrix\Timeman\Helper\Form\Schedule;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleCollection;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Repository\DepartmentRepository;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\TimemanUrlManager;

Loc::loadMessages(__FILE__);

class ScheduleFormHelper
{
	/*** @var DepartmentRepository */
	private $departmentRepository;
	/*** @var ScheduleRepository */
	private $scheduleRepository;
	private $userHelper;

	public function __construct($scheduleRepository = null, $departmentRepository = null, $userHelper = null)
	{
		$this->scheduleRepository = $scheduleRepository ?: DependencyManager::getInstance()->getScheduleRepository();
		$this->departmentRepository = $departmentRepository ?: DependencyManager::getInstance()->getDepartmentRepository();
		$this->userHelper = $userHelper ?: UserHelper::getInstance();
	}

	public static function getScheduleTypesValues()
	{
		return array_keys(static::getScheduleTypes());
	}

	public static function getScheduleTypes()
	{
		return [
			ScheduleTable::SCHEDULE_TYPE_FIXED => Loc::getMessage('TIMEMAN_SCHEDULE_SHIFT_EDIT_SELECT_TYPE_FIXED'),
			ScheduleTable::SCHEDULE_TYPE_FLEXTIME => Loc::getMessage('TIMEMAN_SCHEDULE_SHIFT_EDIT_SELECT_TYPE_FLEXTIME'),
			ScheduleTable::SCHEDULE_TYPE_SHIFT => Loc::getMessage('TIMEMAN_SCHEDULE_SHIFT_EDIT_SELECT_TYPE_SHIFT'),
		];
	}

	public static function getReportPeriods()
	{
		return [
			ScheduleTable::REPORT_PERIOD_MONTH => Loc::getMessage('TIMEMAN_SCHEDULE_SHIFT_EDIT_SELECT_REPORT_PERIOD_MONTH'),
			ScheduleTable::REPORT_PERIOD_WEEK => Loc::getMessage('TIMEMAN_SCHEDULE_SHIFT_EDIT_SELECT_REPORT_PERIOD_WEEK'),
			ScheduleTable::REPORT_PERIOD_TWO_WEEKS => Loc::getMessage('TIMEMAN_SCHEDULE_SHIFT_EDIT_SELECT_REPORT_PERIOD_TWO_WEEKS'),
			ScheduleTable::REPORT_PERIOD_QUARTER => Loc::getMessage('TIMEMAN_SCHEDULE_SHIFT_EDIT_SELECT_REPORT_PERIOD_QUARTER'),
		];
	}

	public static function getControlledActionValues()
	{
		return array_keys(static::getControlledActionTypes());
	}

	public static function getControlledActionTypes()
	{
		return [
			ScheduleTable::CONTROLLED_ACTION_START_AND_END => Loc::getMessage('TIMEMAN_SCHEDULE_SHIFT_EDIT_SELECT_CONTROLLED_START_END'),
			ScheduleTable::CONTROLLED_ACTION_START => Loc::getMessage('TIMEMAN_SCHEDULE_SHIFT_EDIT_SELECT_CONTROLLED_START'),
		];
	}

	public static function getReportPeriodWeekDaysValues()
	{
		return array_keys(static::getReportPeriodWeekDays());
	}

	public static function getReportPeriodWeekDays()
	{
		return [
			ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_MONDAY => Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_MON'),
			ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_TUESDAY => Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_TUE'),
			ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_WEDNESDAY => Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_WED'),
			ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_THURSDAY => Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_THU'),
			ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_FRIDAY => Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_FRI'),
			ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_SATURDAY => Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_SAT'),
			ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_SUNDAY => Loc::getMessage('TIMEMAN_SCHEDULE_EDIT_SUN'),
		];
	}

	public static function getReportPeriodsValues()
	{
		return array_keys(static::getReportPeriods());
	}

	public function calculateSchedulesMapBySchedule($schedule, $checkNestedEntities = true)
	{
		$result = $this->calculateScheduleAssignmentsMap($schedule, $checkNestedEntities);
		return $this->fillExtraInfoToSchedulesMap($result);
	}

	public function calculateSchedulesMapByCodes($codesToCheck, $checkNestedEntities = false)
	{
		$schedule = (new Schedule(false))->setId(0);
		foreach ($codesToCheck as $code)
		{
			$schedule->assignEntity($code);
		}
		$result = $this->calculateScheduleAssignmentsMap($schedule, $checkNestedEntities);
		return $this->fillExtraInfoToSchedulesMap($result);

	}

	/**
	 * @param $codesToCheck
	 * @param Schedule|null $checkingSchedule
	 * @param bool|null $checkNestedEntities
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function calculateScheduleAssignmentsMap($checkingSchedule, $checkNestedEntities = false)
	{
		$codesToCheck = [];
		foreach ($checkingSchedule->obtainUserAssignments()->getAll() as $assignment)
		{
			if ($assignment->isIncluded())
			{
				$codesToCheck[] = $assignment->getEntityCode();
			}
		}
		foreach ($checkingSchedule->obtainDepartmentAssignments()->getAll() as $scheduleDepartment)
		{
			if ($scheduleDepartment->isIncluded())
			{
				$codesToCheck[] = $scheduleDepartment->getEntityCode();
			}
		}
		if ($checkingSchedule->getIsForAllUsers())
		{
			$codesToCheck[] = EntityCodesHelper::getAllUsersCode();
		}
		$codesToCheck = $this->prepareEntitiesCodes($codesToCheck);
		if ($checkNestedEntities)
		{
			$codesToCheck = $this->extendWithNestedEntitiesCodes($codesToCheck);
		}

		$result = [];

		$schedulesWithAssignments = $this->findSchedulesByAssignmentsCodes($codesToCheck, $checkingSchedule);
		if ($schedulesWithAssignments->count() === 0)
		{
			return $result;
		}


		$checkingCodesMap = $this->buildSignMapBySchedule($checkingSchedule);
		$signMaps = [];
		$baseDepartment = $this->departmentRepository->getBaseDepartmentId();
		foreach ($schedulesWithAssignments->getAll() as $schedule)
		{
			$signMap = $this->buildSignMapBySchedule($schedule);
			foreach ($checkingCodesMap as $codeToCheck => $codeIncluded)
			{
				if (
					mb_substr($signMap[$codeToCheck] ?? '', 0, 8) === 'included'
					&& mb_substr($codeIncluded, 0, 8) === 'included'
				)
				{
					$result[$codeToCheck][$schedule->getId()] = $schedule;
				}
			}
			$signMaps[$schedule->getId()] = $signMap;
		}

		$resultToShow = [];
		foreach ($result as $code => $schedules)
		{
			foreach ($schedules as $schedule)
			{
				$directParentCodes = [];
				if (EntityCodesHelper::isUser($code))
				{
					$directParentCodes = EntityCodesHelper::buildDepartmentCodes(
						$this->departmentRepository->getDirectParentIdsByUserId(EntityCodesHelper::getUserId($code))
					);
				}
				elseif (EntityCodesHelper::isDepartment($code))
				{
					$directParentCodes = EntityCodesHelper::buildDepartmentCodes(
						$this->departmentRepository->getDirectParentIdsByDepartmentId(EntityCodesHelper::getDepartmentId($code))
					);
				}
				$includedPersonal = false;
				if (!empty($result[$code]))
				{
					foreach ($result[$code] as $codeSchedule)
					{
						if ($signMaps[$codeSchedule->getId()][$code] === 'includedPersonal')
						{
							$includedPersonal = true;
						}
					}
				}
				$includedByParent = false;
				foreach ($directParentCodes as $directParentCode)
				{
					if (!empty($result[$directParentCode]) && in_array($schedule->getId(), array_keys($result[$directParentCode])))
					{
						$includedByParent = true;
					}
				}
				if (!$includedByParent || $includedPersonal)
				{
					if (EntityCodesHelper::getDepartmentId($code) === $baseDepartment)
					{
						$resultToShow[EntityCodesHelper::getAllUsersCode()][$schedule->getId()] = $schedule;
					}
					else
					{
						$resultToShow[$code][$schedule->getId()] = $schedule;
					}
				}
			}
		}

		return $resultToShow;
	}

	private function getSign($parentSign, $code, $signMap)
	{
		if (
			($signMap[$code] ?? null) === 'excludedByParent'
			&& (
				$parentSign == 'includedPersonal'
				|| $parentSign == 'includedByParent'
			)
		)
		{
			return $this->buildSign($parentSign);
		}

		if (!array_key_exists($code, $signMap))
		{
			return $this->buildSign($parentSign);
		}

		return $signMap[$code];
	}

	private function fillScheduleSignMap($parentSign, $selfCode, &$signMap)
	{
		$signMap[$selfCode] = $this->getSign($parentSign, $selfCode, $signMap);

		static $directChildrenUsersMap = [];
		if (
			EntityCodesHelper::isDepartment($selfCode)
			&& ($directChildrenUsersMap[$selfCode] ?? null) === null
		)
		{
			$directChildrenUsersMap[$selfCode] = EntityCodesHelper::buildUserCodes(
				$this->departmentRepository->getUsersOfDepartment(EntityCodesHelper::getDepartmentId($selfCode))
			);
		}
		static $directChildrenMap = null;
		if ($directChildrenMap === null)
		{
			$directChildrenMap = $this->departmentRepository->getDepartmentsTree();
			foreach ($directChildrenMap as $index => $data)
			{
				$directChildrenMap[EntityCodesHelper::buildDepartmentCode($index)] = EntityCodesHelper::buildDepartmentCodes($data);
				unset($directChildrenMap[$index]);
			}
			$directChildrenMap = array_filter($directChildrenMap);
		}
		if (array_key_exists($selfCode, $directChildrenMap))
		{
			foreach ($directChildrenMap[$selfCode] as $childCode)
			{
				$this->fillScheduleSignMap($signMap[$selfCode], $childCode, $signMap);
			}
		}
		if (array_key_exists($selfCode, $directChildrenUsersMap))
		{
			foreach ($directChildrenUsersMap[$selfCode] as $userCode)
			{
				$signMap[$userCode] = $this->getSign($signMap[$selfCode], $userCode, $signMap);

				$this->fillScheduleSignMap($signMap[$userCode], $userCode, $signMap);
			}
		}
	}

	private function buildSignMapBySchedule(Schedule $schedule)
	{
		$baseDepartment = $this->departmentRepository->getBaseDepartmentId();
		$signMap = [];
		foreach ($schedule->obtainDepartmentAssignments() as $depAssign)
		{
			$signMap[EntityCodesHelper::buildDepartmentCode($depAssign->getDepartmentId())] = $depAssign->isIncluded() ? 'includedPersonal' : 'excludedPersonal';
		}
		if ($schedule->getIsForAllUsers() && $baseDepartment > 0)
		{
			$signMap[EntityCodesHelper::buildDepartmentCode($baseDepartment)] = 'includedPersonal';
		}
		foreach ($schedule->obtainUserAssignments() as $userAssign)
		{
			$signMap[EntityCodesHelper::buildUserCode($userAssign->getUserId())] = $userAssign->isIncluded() ? 'includedPersonal' : 'excludedPersonal';
		}

		$this->fillScheduleSignMap($schedule->getIsForAllUsers() ? 'includedPersonal' : 'excludedByParent', EntityCodesHelper::buildDepartmentCode($baseDepartment), $signMap);
		return $signMap;
	}

	private function buildSign($parentSign)
	{
		if ($parentSign === 'excludedPersonal')
		{
			return 'excludedByParent';
		}
		if ($parentSign === 'includedPersonal')
		{
			return 'includedByParent';
		}
		return $parentSign;
	}

	public function fillExtraInfoToSchedulesMap($resultSchedulesMap)
	{
		$schedulesForAllUsers = array_map(
			'intval',
			array_column(
				(array) ($resultSchedulesMap[EntityCodesHelper::getAllUsersCode()] ?? []),
				'ID'
			)
		);

		return $this->prepareResult($resultSchedulesMap, $schedulesForAllUsers);
	}

	private function prepareResult($resultSchedulesMap, $schedulesForAllUsers = [])
	{
		$userIdsForName = [];
		foreach ($resultSchedulesMap as $index => $schedules)
		{
			if (EntityCodesHelper::isUser($index))
			{
				$userIdsForName[EntityCodesHelper::getUserId($index)] = EntityCodesHelper::getUserId($index);
			}
		}
		if (!empty($userIdsForName))
		{
			$names = $this->findUserNames($userIdsForName);
		}
		$result = [];
		foreach ($resultSchedulesMap as $index => $schedules)
		{
			$result[$index] = [];
			foreach ($schedules as $scheduleIndex => $schedule)
			{
				$result[$index][$scheduleIndex] = $schedule->collectRawValues();
			}
		}
		foreach ($resultSchedulesMap as $index => $schedules)
		{
			foreach ($schedules as $scheduleIndex => $schedule)
			{
				$result[$index][$scheduleIndex] = array_merge(
					$result[$index][$scheduleIndex],
					[
						'LINKS' => [
							'DETAIL' => DependencyManager::getInstance()->getUrlManager()
								->getUriTo(TimemanUrlManager::URI_SCHEDULE_UPDATE, ['SCHEDULE_ID' => $schedule->getId()]),
						],
					]
				);
				if (EntityCodesHelper::isUser($index) && !empty($names[EntityCodesHelper::getUserId($index)]))
				{
					$result[$index][$scheduleIndex]['entityName'] = $this->userHelper->getFormattedName(
						$names[EntityCodesHelper::getUserId($index)]
					);
					$result[$index][$scheduleIndex]['entityGender'] = $names[EntityCodesHelper::getUserId($index)]['PERSONAL_GENDER'];
				}
				else
				{
					$result[$index][$scheduleIndex]['entityName'] = $this->getDepartmentName(EntityCodesHelper::getDepartmentId($index));
				}
			}
		}

		foreach ($resultSchedulesMap as $index => $schedules)
		{
			foreach ($schedules as $scheduleIndex => $schedule)
			{
				if (in_array((int)$schedule->getId(), $schedulesForAllUsers, true))
				{
					$result[$index][$scheduleIndex]['entityCode'] = EntityCodesHelper::getAllUsersCode();
				}
			}
		}
		return $result;
	}

	/**
	 * @param $entitiesCodes
	 * @return array
	 */
	private function prepareEntitiesCodes($entitiesCodes)
	{
		if (!is_array($entitiesCodes))
		{
			$entitiesCodes = [$entitiesCodes];
		}
		foreach ($entitiesCodes as $entityCode)
		{
			if ($entityCode === EntityCodesHelper::getAllUsersCode())
			{
				if ($this->getDepartmentRepository()->getBaseDepartmentId() > 0)
				{
					$entitiesCodes[] = EntityCodesHelper::buildDepartmentCode($this->getDepartmentRepository()->getBaseDepartmentId());
					break;
				}
			}
		}
		return array_unique(array_filter($entitiesCodes));
	}

	private function getDepartmentRepository()
	{
		return $this->departmentRepository;
	}

	protected function findUserNames($userIdsForName)
	{
		$userQueryResult = $this->scheduleRepository
			->getUsersBaseQuery()
			->addSelect('PERSONAL_GENDER')
			->whereIn('ID', $userIdsForName)
			->exec()
			->fetchAll();
		return array_combine(
			array_column($userQueryResult, 'ID'),
			$userQueryResult
		);
	}

	protected function getDepartmentName($departmentId)
	{
		$data = $this->getDepartmentRepository()->getAllData();
		return !empty($data[$departmentId]['NAME']) ? $data[$departmentId]['NAME'] : null;
	}

	public function getFormattedType($type)
	{
		return isset(static::getScheduleTypes()[$type])
			? static::getScheduleTypes()[$type]
			: '';
	}

	public function getFormattedPeriod($period)
	{
		return isset(static::getReportPeriods()[$period])
			? static::getReportPeriods()[$period]
			: '';
	}

	private function extendWithParentDepartmentCodes(array $entitiesCodes)
	{
		$allCodes = [];
		foreach ($entitiesCodes as $entityCode)
		{
			if (EntityCodesHelper::isUser($entityCode))
			{
				$allCodes[$entityCode] = $entityCode;
				foreach ($this->departmentRepository->getAllUserDepartmentIds(EntityCodesHelper::getUserId($entityCode)) as $item)
				{
					$allCodes[EntityCodesHelper::buildDepartmentCode($item)] = $item;
				}
			}
			elseif (EntityCodesHelper::isDepartment($entityCode))
			{
				$allCodes[$entityCode] = $entityCode;
				foreach ($this->departmentRepository->getAllParentDepartmentsIds(EntityCodesHelper::getDepartmentId($entityCode)) as $item)
				{
					$allCodes[EntityCodesHelper::buildDepartmentCode($item)] = $item;
				}
			}
		}
		return array_unique(array_keys($allCodes));
	}

	private function extendWithNestedEntitiesCodes(array $entitiesCodes)
	{
		$allCodes = [];
		foreach ($entitiesCodes as $entityCode)
		{
			if (EntityCodesHelper::isUser($entityCode))
			{
				$allCodes[$entityCode] = $entityCode;
			}
			elseif (EntityCodesHelper::isDepartment($entityCode))
			{
				$departmentId = EntityCodesHelper::getDepartmentId($entityCode);
				$allDepartments = array_merge(
					[$departmentId],
					$this->getDepartmentRepository()->getAllChildDepartmentsIds($departmentId)
				);
				foreach (EntityCodesHelper::buildDepartmentCodes($allDepartments) as $item)
				{
					$allCodes[$item] = $item;
				}

				foreach ($allDepartments as $depId)
				{
					$userIds = $this->getDepartmentRepository()->getUsersOfDepartment($depId);
					foreach (EntityCodesHelper::buildUserCodes($userIds) as $item)
					{
						$allCodes[$item] = $item;
					}
				}
			}
		}
		return array_keys($allCodes);
	}

	/**
	 * @param array $codesToCheck
	 * @param Schedule|null $showingSchedule
	 * @return ScheduleCollection
	 */
	private function findSchedulesByAssignmentsCodes(array $codesToCheck, $showingSchedule)
	{
		$schedulesCollection = new ScheduleCollection();
		if (empty($codesToCheck))
		{
			return $schedulesCollection;
		}
		$allPossibleCodes = $this->extendWithParentDepartmentCodes($codesToCheck);

		/** @var \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser[] $userAssignments */
		$userAssignments = $this->scheduleRepository->findUserAssignmentsByIds(
			EntityCodesHelper::extractUserIdsFromEntityCodes($allPossibleCodes),
			$showingSchedule ? $showingSchedule->getId() : null
		);

		foreach ($userAssignments as $userAssignment)
		{
			$schedule = $schedulesCollection->getByPrimary($userAssignment->getScheduleId());
			if (!$schedule)
			{
				$schedule = new Schedule(false);
				$schedule->setId($userAssignment->getScheduleId());
				$schedulesCollection->add($schedule);
			}
			$schedule->addToUserAssignments($userAssignment);
		}

		/** @var ScheduleDepartment[] $departmentAssignments */
		$departmentAssignments = $this->scheduleRepository->findDepartmentAssignmentsByIds(
			EntityCodesHelper::extractDepartmentIdsFromEntityCodes($allPossibleCodes),
			$showingSchedule ? $showingSchedule->getId() : null
		);
		foreach ($departmentAssignments as $departmentAssignment)
		{
			$schedule = $schedulesCollection->getByPrimary($departmentAssignment->getScheduleId());
			if (!$schedule)
			{
				$schedule = new Schedule(false);
				$schedule->setId($departmentAssignment->getScheduleId());
				$schedulesCollection->add($schedule);
			}
			$schedule->addToDepartmentAssignments($departmentAssignment);
		}

		$filter = Query::filter()->where('IS_FOR_ALL_USERS', true);
		if ($showingSchedule)
		{
			$filter->whereNot('ID', $showingSchedule->getId());
		}
		$forAllUsers = $this->scheduleRepository->findAllBy(
			['ID', 'NAME', 'IS_FOR_ALL_USERS', 'DEPARTMENT_ASSIGNMENTS', 'USER_ASSIGNMENTS',],
			$filter
		);
		foreach ($forAllUsers->getAll() as $scheduleForAllUsers)
		{
			$schedule = $schedulesCollection->getByPrimary($scheduleForAllUsers->getId());
			if (!$schedule)
			{
				$schedulesCollection->add($scheduleForAllUsers);
			}
			else
			{
				$schedule->setIsForAllUsers(true);
				$schedule->setName($scheduleForAllUsers->getName());
			}
		}

		$needExtraIds = array_diff($schedulesCollection->getIdList(), $forAllUsers->getIdList());
		if (count($needExtraIds) > 0)
		{
			$schedulesWithExtraData = $this->scheduleRepository->findAllBy(
				['ID', 'NAME', 'IS_FOR_ALL_USERS'],
				Query::filter()
					->whereIn('ID', $needExtraIds)
			);
			foreach ($schedulesWithExtraData->getAll() as $item)
			{
				$schedulesCollection->getByPrimary($item->getId())
					->setIsForAllUsers($item->getIsForAllUsers());
				$schedulesCollection->getByPrimary($item->getId())
					->setName($item->getName());
			}
		}
		return $schedulesCollection;
	}
}