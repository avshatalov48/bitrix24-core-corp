<?php
namespace Bitrix\Timeman\Helper\Form\Schedule;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser;
use Bitrix\Timeman\Model\Schedule\Schedule;
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

	public function calculateScheduleAssignmentsMap($entitiesCodes, $showingSchedule = null)
	{
		$entitiesCodes = $this->prepareEntitiesCodes($entitiesCodes);
		$resultSchedulesMap = $this->fillSchedulesForAllUsers($entitiesCodes);
		$schedulesForAllUsers = array_map('intval', array_column((array)$resultSchedulesMap[EntityCodesHelper::getAllUsersCode()], 'ID'));
		$allCodes = [];
		foreach ($entitiesCodes as $entityCode)
		{
			if (EntityCodesHelper::isUser($entityCode))
			{
				$allCodes[] = $entityCode;
			}
			elseif (EntityCodesHelper::isDepartment($entityCode))
			{
				$allDepartments = array_merge(
					[EntityCodesHelper::getDepartmentId($entityCode)],
					$this->getDepartmentRepository()->getAllChildDepartmentsIds(EntityCodesHelper::getDepartmentId($entityCode))
				);
				$allCodes = array_merge($allCodes, EntityCodesHelper::buildDepartmentCodes($allDepartments));

				foreach ($allDepartments as $depId)
				{
					$userIds = $this->getDepartmentRepository()->getUsersOfDepartment($depId);
					$allCodes = array_merge($allCodes, EntityCodesHelper::buildUserCodes($userIds));
				}
			}
		}
		$schedulesMap = $this->scheduleRepository
			->findSchedulesByEntityCodes($allCodes, ['select' => ['ID', 'NAME', 'IS_FOR_ALL_USERS']]);


		$entitiesCodes = $this->sortCodes($allCodes);
		foreach ($entitiesCodes as $entityCode)
		{
			if (EntityCodesHelper::isUser($entityCode))
			{
				$userSchedules = $this->fillUserSchedulesMap($entityCode, $schedulesMap, $resultSchedulesMap);
				if (!empty($userSchedules))
				{
					$resultSchedulesMap[$entityCode] = $userSchedules;
				}
			}
			elseif (EntityCodesHelper::isDepartment($entityCode))
			{
				$this->buildScheduleAssignmentsUniqueMap(EntityCodesHelper::getDepartmentId($entityCode), $schedulesMap, $resultSchedulesMap);
			}
		}
		$resultSchedulesMap = $this->fillUserNames($resultSchedulesMap, $schedulesForAllUsers);
		$resultSchedulesMap = array_filter($this->filterByCurrentScheduleId($resultSchedulesMap, $showingSchedule));

		foreach ($resultSchedulesMap as $entCode => $assignSchedules)
		{
			foreach ($assignSchedules as $index => $assignSchedule)
			{
				if (!empty($assignSchedule['entityCode'])
					&& EntityCodesHelper::getDepartmentId($assignSchedule['entityCode']) === $this->getDepartmentRepository()->getBaseDepartmentId())
				{
					$resultSchedulesMap[EntityCodesHelper::getAllUsersCode()] = $resultSchedulesMap[$entCode];
					break;
				}
			}
		}

		return $resultSchedulesMap;
	}

	private function fillUserNames($resultSchedulesMap, $schedulesForAllUsers = [])
	{
		$userIdsForName = [];
		foreach ($resultSchedulesMap as $index => $resultData)
		{
			if (EntityCodesHelper::isUser($index))
			{
				$userIdsForName[EntityCodesHelper::getUserId($index)] = EntityCodesHelper::getUserId($index);
			}
		}
		if (!empty($userIdsForName))
		{
			$names = $this->findUserNames($userIdsForName);
			if (!empty($names))
			{
				foreach ($resultSchedulesMap as $index => $resultData)
				{
					if (EntityCodesHelper::isUser($index) && !empty($names[EntityCodesHelper::getUserId($index)]))
					{
						foreach ($resultSchedulesMap[$index] as $scheduleIndex => $item)
						{
							$resultSchedulesMap[$index][$scheduleIndex]['entityName'] = $this->userHelper->getFormattedName(
								$names[EntityCodesHelper::getUserId($index)]
							);
							$resultSchedulesMap[$index][$scheduleIndex]['entityGender'] = $names[EntityCodesHelper::getUserId($index)]['PERSONAL_GENDER'];
						}
					}
				}
			}
		}
		foreach ($resultSchedulesMap as $index => $resultData)
		{
			foreach ($resultSchedulesMap[$index] as $scheduleIndex => $item)
			{
				if (in_array((int)$item['ID'], $schedulesForAllUsers, true))
				{
					$resultSchedulesMap[$index][$scheduleIndex]['entityCode'] = EntityCodesHelper::getAllUsersCode();
				}
			}
		}
		return $resultSchedulesMap;
	}

	private function prepareEntitiesCodes($entitiesCodes)
	{
		if (!is_array($entitiesCodes))
		{
			$entitiesCodes = [$entitiesCodes];
		}
		foreach ($entitiesCodes as $entitiesCode)
		{
			if ($entitiesCode === EntityCodesHelper::getAllUsersCode())
			{
				if ($this->getDepartmentRepository()->getBaseDepartmentId() > 0)
				{
					$entitiesCodes[] = EntityCodesHelper::buildDepartmentCode($this->getDepartmentRepository()->getBaseDepartmentId());
					break;
				}
			}

		}
		return array_unique($entitiesCodes);
	}

	private function getDepartmentRepository()
	{
		return $this->departmentRepository;
	}

	/**
	 * @param \Bitrix\Timeman\Model\Schedule\Schedule $schedule
	 * @return array
	 */
	private function makeScheduleResult($schedule, $entityCode)
	{
		return array_merge(
			$schedule->collectValues(),
			[
				'LINKS' => [
					'DETAIL' => DependencyManager::getInstance()->getUrlManager()
						->getUriTo(TimemanUrlManager::URI_SCHEDULE_UPDATE, ['SCHEDULE_ID' => $schedule->getId()]),
				],
				'entityCode' => $entityCode,
			]
		);
	}

	private function buildScheduleAssignmentsUniqueMap($departmentId, $schedulesMap, &$resultSchedulesMap, $shownParentDepartmentIds = [])
	{
		$departmentCode = EntityCodesHelper::buildDepartmentCode($departmentId);

		foreach ((array)$schedulesMap[$departmentCode] as $entitySchedule)
		{
			if ($this->noParentsHaveSchedule($entitySchedule->getId(), $resultSchedulesMap, $departmentCode))
			{
				$result = $this->makeScheduleResult($entitySchedule, $departmentCode);
				if ($this->getDepartmentName($departmentId))
				{
					$result['entityName'] = $this->getDepartmentName($departmentId);
				}
				$resultSchedulesMap[$departmentCode][$entitySchedule->getId()] = $result;
			}
		}

		$shownParentDepartmentIds[$departmentId] = $departmentId;

		$userIds = $this->getDepartmentRepository()->getUsersOfDepartment($departmentId);
		foreach ($userIds as $userId)
		{
			$code = EntityCodesHelper::buildUserCode($userId);
			$userSchedules = $this->fillUserSchedulesMap($code, $schedulesMap, $resultSchedulesMap);
			if (!empty($userSchedules))
			{
				$resultSchedulesMap[$code] = $userSchedules;
			}
		}

		$subDepartmentsIds = $this->getDepartmentRepository()->getSubDepartmentsIds($departmentId);
		foreach ($subDepartmentsIds as $subDepartmentId)
		{
			$this->buildScheduleAssignmentsUniqueMap($subDepartmentId, $schedulesMap, $resultSchedulesMap, $shownParentDepartmentIds);
		}
	}

	private function noParentsHaveSchedule($scheduleId, $resultSchedulesMap, $code)
	{
		if (EntityCodesHelper::isDepartment($code))
		{
			$parentDepartmentIds = $this->getDepartmentRepository()->getAllParentDepartmentsIds(EntityCodesHelper::getDepartmentId($code));
		}
		else
		{
			$userDepartmentIds = $this->getDepartmentRepository()->getUserDepartmentsIds(EntityCodesHelper::getUserId($code));
			$parentDepartmentIds = $userDepartmentIds;
			foreach ($userDepartmentIds as $userDepartmentId)
			{
				$parentDepartmentIds = array_merge($parentDepartmentIds, $this->getDepartmentRepository()->getAllParentDepartmentsIds($userDepartmentId));
			}
			$parentDepartmentIds = array_unique($parentDepartmentIds);
		}
		foreach ($parentDepartmentIds as $parentDepartmentId)
		{
			if (empty($resultSchedulesMap[EntityCodesHelper::buildDepartmentCode($parentDepartmentId)]))
			{
				continue;
			}
			foreach ((array)$resultSchedulesMap[EntityCodesHelper::buildDepartmentCode($parentDepartmentId)] as $parentSchedule)
			{
				if ($parentSchedule['ID'] === $scheduleId)
				{
					return false;
				}
			}
		}
		return true;
	}

	private function fillSchedulesForAllUsers($entitiesCodes)
	{
		$resultSchedulesMap = [];
		foreach ($entitiesCodes as $entityCode)
		{
			if ($entityCode === 'UA')
			{
				$commonSchedules = $this->scheduleRepository
					->findAllBy(
						['ID', 'IS_FOR_ALL_USERS', 'NAME'],
						Query::filter()->where('IS_FOR_ALL_USERS', true),
						5
					);
				foreach ($commonSchedules as $commonSchedule)
				{
					$resultSchedulesMap[$entityCode][$commonSchedule['ID']] = $this->makeScheduleResult($commonSchedule, $entityCode);
				}
				break;
			}
		}
		return $resultSchedulesMap;
	}

	private function fillUserSchedulesMap($code, $schedulesMap, $resultSchedulesMap)
	{
		$result = [];
		foreach ((array)$schedulesMap[$code] as $entitySchedule)
		{
			if ($this->noParentsHaveSchedule($entitySchedule->getId(), $resultSchedulesMap, $code))
			{
				$result[$entitySchedule->getId()] = $this->makeScheduleResult($entitySchedule, $code);
			}
		}
		return $result;
	}

	private function sortCodes($entitiesCodes)
	{
		$userIds = EntityCodesHelper::extractUserIdsFromEntityCodes($entitiesCodes);
		$depIds = EntityCodesHelper::extractDepartmentIdsFromEntityCodes($entitiesCodes);
		if (!empty($depIds))
		{
			$result = [];
			$structureTree = \CIntranetUtils::GetStructure()['DATA'];
			foreach ($depIds as $depId)
			{
				$result[] = [
					'ID' => $depId,
					'DEPTH_LEVEL' => $structureTree[$depId]['DEPTH_LEVEL'],
				];
			}
			\Bitrix\Main\Type\Collection::sortByColumn(
				$result,
				['DEPTH_LEVEL' => SORT_ASC]
			);
			$depIds = EntityCodesHelper::buildDepartmentCodes(array_column($result, 'ID'));
		}

		return array_merge(
			$depIds,
			EntityCodesHelper::buildUserCodes($userIds)
		);
	}

	/**
	 * @param $resultSchedulesMap
	 * @param Schedule $showingSchedule
	 * @return mixed
	 */
	private function filterByCurrentScheduleId($resultSchedulesMap, $showingSchedule)
	{
		if (!$showingSchedule)
		{
			return $resultSchedulesMap;
		}
		foreach ($resultSchedulesMap as $entityCode => $entitySchedules)
		{
			if (!empty($resultSchedulesMap[$entityCode]))
			{
				foreach ($entitySchedules as $entityScheduleIndex => $entitySchedule)
				{
					if ($entitySchedule['ID'] == $showingSchedule['ID'] || $this->isEntityExcluded($entityCode, $showingSchedule))
					{
						unset($resultSchedulesMap[$entityCode][$entityScheduleIndex]);
					}
				}
			}
		}

		return $resultSchedulesMap;
	}

	/**
	 * @param $entityCode
	 * @param Schedule $showingSchedule
	 */
	private function isEntityExcluded($entityCode, $showingSchedule)
	{
		if (EntityCodesHelper::isUser($entityCode))
		{
			foreach ($showingSchedule->obtainUserAssignments() as $userAssignment)
			{
				if ($entityCode === EntityCodesHelper::buildUserCode($userAssignment['USER_ID'])
					&& ScheduleUser::isUserExcluded($userAssignment))
				{
					return true;
				}
			}
			$userDepartmentsTree = $this->getDepartmentRepository()
				->buildUserDepartmentsPriorityTree(EntityCodesHelper::getUserId($entityCode));
			foreach ($userDepartmentsTree as $userDepartmentsTreeData)
			{
				foreach ($userDepartmentsTreeData as $treeEntityCode)
				{
					if (EntityCodesHelper::isDepartment($treeEntityCode))
					{
						foreach ($showingSchedule->obtainDepartmentAssignments() as $departmentAssignment)
						{
							if ($treeEntityCode === EntityCodesHelper::buildDepartmentCode($departmentAssignment['DEPARTMENT_ID'])
								&& ScheduleDepartment::isDepartmentExcluded($departmentAssignment))
							{
								if ($showingSchedule->obtainUserAssignmentsById(EntityCodesHelper::getUserId($entityCode))
									&& !ScheduleUser::isUserIncluded($showingSchedule->obtainUserAssignmentsById(EntityCodesHelper::getUserId($entityCode))))
								{
									return true;
								}
							}
						}
					}
				}
			}
		}
		elseif (EntityCodesHelper::isDepartment($entityCode))
		{
			foreach ($showingSchedule->obtainDepartmentAssignments() as $departmentAssignment)
			{
				if ($entityCode === EntityCodesHelper::buildDepartmentCode($departmentAssignment['DEPARTMENT_ID'])
					&& ScheduleDepartment::isDepartmentExcluded($departmentAssignment))
				{
					return true;
				}
			}
			$parents = $this->getDepartmentRepository()
				->getAllParentDepartmentsIds(EntityCodesHelper::getDepartmentId($entityCode));
			$excludedByParent = false;
			$includedIndividually = false;

			foreach ($showingSchedule->obtainDepartmentAssignments() as $departmentAssignment)
			{
				if ($entityCode === EntityCodesHelper::buildDepartmentCode($departmentAssignment['DEPARTMENT_ID'])
					&& ScheduleDepartment::isDepartmentIncluded($departmentAssignment))
				{
					$includedIndividually = true;
				}
				foreach ($parents as $parentDepId)
				{
					if (EntityCodesHelper::buildDepartmentCode($parentDepId) === EntityCodesHelper::buildDepartmentCode($departmentAssignment['DEPARTMENT_ID'])
						&& ScheduleDepartment::isDepartmentExcluded($departmentAssignment)
					)
					{
						$excludedByParent = true;
					}
				}
			}
			return $excludedByParent && !$includedIndividually;
		}
		return false;
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
		return !empty(\CIntranetUtils::getStructure()['DATA'][$departmentId]['NAME']) ? \CIntranetUtils::getStructure()['DATA'][$departmentId]['NAME'] : null;
	}
}