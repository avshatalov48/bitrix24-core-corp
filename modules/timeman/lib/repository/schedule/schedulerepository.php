<?php
namespace Bitrix\Timeman\Repository\Schedule;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartmentTable;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleCollection;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftTable;
use Bitrix\Timeman\Model\User\EO_User_Query;
use Bitrix\Timeman\Model\User\UserTable;
use Bitrix\Timeman\Repository\DepartmentRepository;
use CIntranetUtils;

\Bitrix\Main\Loader::includeModule('intranet');

class ScheduleRepository
{
	/** @var DepartmentRepository */
	private $departmentRepository;

	public function __construct(DepartmentRepository $departmentRepository)
	{
		$this->departmentRepository = $departmentRepository;
	}

	public function findById($id)
	{
		return ScheduleTable::getById($id)->fetchObject();
	}

	public function queryEmployees()
	{
		$res = $this->getUsersBaseQuery();
		$this->addUserEmployeeCondition($res);
		return $res;
	}

	/**
	 * @param $id
	 * @return \Bitrix\Timeman\Model\Schedule\Schedule|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByIdWithShifts($id)
	{
		if (!($id > 0))
		{
			return null;
		}
		return $this->getActiveSchedulesQuery()
			->addSelect('*')
			->addSelect('SHIFTS')
			->where('ID', $id)
			->where(Query::filter()->logic('or')
				->where('SHIFTS.DELETED', ShiftTable::DELETED_NO)
				->whereNull('SHIFTS.DELETED')
			)
			->exec()
			->fetchObject();
	}

	/**
	 * @param $id
	 * @param array $withEntities
	 * @return Schedule
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByIdWith($id, $withEntities = [])
	{
		$query = $this->getActiveSchedulesQuery()
			->addSelect('*')
			->where('ID', $id);

		foreach ($withEntities as $with)
		{
			switch ($with)
			{
				case 'SCHEDULE_VIOLATION_RULES':
					$query->addSelect('SCHEDULE_VIOLATION_RULES');
					break;
				case 'SHIFTS':
					$query->addSelect($with);
					$query->where(Query::filter()->logic('or')
						->where('SHIFTS.DELETED', ShiftTable::DELETED_NO)
						->whereNull('SHIFTS.DELETED')
					);
					$query->addOrder('SHIFTS.ID');
					break;
				case 'CALENDAR':
				case 'CALENDAR.EXCLUSIONS':
				case 'CALENDAR.PARENT_CALENDAR.EXCLUSIONS':
				case 'CALENDAR.PARENT_CALENDAR.ID':
					$query->addSelect($with);
					break;
				default:
					break;
			}
		}
		/** @var Schedule $schedule */
		$schedule = $query->exec()->fetchObject();
		if ($schedule && (in_array('DEPARTMENTS', $withEntities, true) || in_array('DEPARTMENT_ASSIGNMENTS', $withEntities, true))
		)
		{
			$depAssigns = ScheduleDepartmentTable::query()
				->addSelect('*')
				->where('SCHEDULE_ID', $schedule->getId())
				->exec()
				->fetchCollection();
			foreach ($depAssigns as $depAssign)
			{
				$schedule->addTo('DEPARTMENT_ASSIGNMENTS', $depAssign);
			}
		}
		if ($schedule && in_array('USER_ASSIGNMENTS', $withEntities, true))
		{
			$userAssigns = ScheduleUserTable::query()
				->addSelect('*')
				->where('SCHEDULE_ID', $schedule->getId())
				->exec()
				->fetchCollection();
			foreach ($userAssigns as $userAssign)
			{
				$schedule->addTo('USER_ASSIGNMENTS', $userAssign);
			}
		}
		return $schedule;
	}

	public function save(Schedule $schedule)
	{
		return $schedule->save();
	}

	public function findAll()
	{
		return $this->getActiveSchedulesQuery()
			->addSelect('*')
			->addSelect('SHIFTS')
			->addSelect('USERS')
			->registerRuntimeField('USERS', (new OneToMany('USERS', \Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable::class, 'SCHEDULE'))->configureJoinType('LEFT'))
			->where(Query::filter()->logic('or')
				->where('SHIFTS.DELETED', ShiftTable::DELETED_NO)
				->whereNull('SHIFTS.DELETED')
			)
			->exec()
			->fetchCollection();
	}

	private function updateBatchAssignments($assignIds, $scheduleId, $excluded, $assignClassName, $assignName)
	{
		$assignIds = $this->convertEachToInt($assignIds);
		$scheduleId = (int)$scheduleId;
		$excluded = (int)$excluded;
		if (empty($assignIds) || $scheduleId <= 0)
		{
			return new Result();
		}
		$primaries = [];
		foreach ($assignIds as $assignId)
		{
			$primaries[] = [
				'SCHEDULE_ID' => $scheduleId,
				$assignName => (int)$assignId,
			];
		}
		/** @var ScheduleUserTable|Department\ScheduleDepartmentTable $assignClassName */
		return $assignClassName::updateMulti($primaries, ['STATUS' => $excluded], true);
	}

	public function updateBatchUsers($scheduleId, $userIds, $excluded)
	{
		return $this->updateBatchAssignments($userIds, $scheduleId, $excluded, ScheduleUserTable::class, 'USER_ID');
	}

	public function updateBatchDepartments($scheduleId, $departmentIds, $excluded)
	{
		return $this->updateBatchAssignments($departmentIds, $scheduleId, $excluded, Department\ScheduleDepartmentTable::class, 'DEPARTMENT_ID');
	}

	/**
	 * @param ScheduleUser[] $users
	 * @return Result
	 */
	public function addBatchUsers($users)
	{
		if (empty($users))
		{
			return new Result();
		}
		$values = [];

		foreach ($users as $user)
		{
			$values[] = [
				'SCHEDULE_ID' => (int)$user->getScheduleId(),
				'USER_ID' => (int)$user->getUserId(),
				'STATUS' => (int)$user->getStatus(),
			];
		}
		return ScheduleUserTable::addMulti($values, true);
	}

	/**
	 * @param Department\ScheduleDepartment[] $departments
	 * @return Result
	 */
	public function addBatchDepartments($departments)
	{
		if (empty($departments))
		{
			return new Result();
		}
		$values = [];

		foreach ($departments as $department)
		{
			$values[] = [
				'SCHEDULE_ID' => (int)$department->getScheduleId(),
				'DEPARTMENT_ID' => (int)$department->getDepartmentId(),
				'STATUS' => (int)$department->getStatus(),
			];
		}
		return Department\ScheduleDepartmentTable::addMulti($values, true);
	}

	public function deleteUsersAssignments($scheduleOrId, $userIds = [])
	{
		return $this->deleteAssignments($userIds, $scheduleOrId, ScheduleUserTable::getTableName(), 'USER_ID');
	}

	public function deleteDepartmentsAssignments($scheduleOrId, $departmentIds = [])
	{
		return $this->deleteAssignments($departmentIds, $scheduleOrId, Department\ScheduleDepartmentTable::getTableName(), 'DEPARTMENT_ID');
	}

	private function deleteAssignments($ids, $scheduleOrId, $tableName, $assignIdName)
	{
		$ids = $this->convertEachToInt($ids);
		if (empty($ids))
		{
			return new Result();
		}
		$schedule = $scheduleOrId;
		if (!($schedule instanceof Schedule))
		{
			$schedule = $this->findById((int)$scheduleOrId);
		}
		if (!$schedule)
		{
			return (new Result())->addError(new Error('Bad params.'));
		}
		$scheduleId = $schedule->getId();

		Application::getConnection()->query(
			'DELETE FROM ' . $tableName . " WHERE SCHEDULE_ID = $scheduleId AND $assignIdName IN (" . implode(',', $ids) . ")"
		);
		return (new Result());
	}

	private function convertEachToInt($ids)
	{
		return array_unique(array_map('intval', $ids));
	}

	public function findActiveByShiftId($shiftId, $select)
	{
		if (empty($select))
		{
			$select = ['ID'];
		}
		$query = $this->getActiveSchedulesQuery()
			->addSelect('SHIFTS.ID');
		$query->where('SHIFTS.ID', $shiftId);
		$query->where('SHIFTS.DELETED', ShiftTable::DELETED_NO);
		foreach ($select as $field)
		{
			$query->addSelect($field);
		}
		return $query
			->exec()
			->fetchObject();
	}

	/**
	 * @param Department\ScheduleDepartment[] $departments
	 */
	private function buildDepartmentsIds($departments)
	{
		$results = [];
		foreach ($departments as $department)
		{
			$results[$department->getDepartmentId()] = $department->getStatus();
			$nestedDeps = $this->findAllNestedDepartmentsIds([$department->getDepartmentId()]);
			foreach ($nestedDeps as $nestedDepId)
			{
				if (!isset($results[$nestedDepId]))
				{
					$results[$nestedDepId] = $department->getStatus();
				}
			}
		}
		return [
			array_keys(array_filter($results, function ($elem) {
				return $elem == 0;
			})),
			array_keys(array_filter($results, function ($elem) {
				return $elem == 1;
			})),
		];
	}

	/**
	 * @return EO_User_Query
	 */
	public function getUsersBaseQuery($idsOnly = false)
	{
		$query = UserTable::query()->addSelect('ID');
		if ($idsOnly)
		{
			return $query;
		}
		$query->addSelect('NAME')
			->addSelect('LAST_NAME')
			->addSelect('SECOND_NAME')
			->addSelect('LOGIN')
			->addSelect('EMAIL')
			->addSelect('PERSONAL_PHOTO')
			->addSelect('WORK_POSITION');
		return $query;
	}

	/**
	 * @param \Bitrix\Main\ORM\Query\Query $query
	 */
	private function addUserEmployeeCondition($query)
	{
		$query->where('USER_TYPE_IS_EMPLOYEE', true);
	}

	public function findAllNestedDepartmentsIds($departmentsIds)
	{
		if (!is_array($departmentsIds))
		{
			$departmentsIds = [$departmentsIds];
		}
		$subResults = [];
		foreach ($departmentsIds as $departmentId)
		{
			$subDepartmentsNested = CIntranetUtils::getSubDepartments($departmentId);
			if (!empty($subDepartmentsNested))
			{
				$subResults = array_merge($subResults, $subDepartmentsNested, $this->findAllNestedDepartmentsIds($subDepartmentsNested));
			}
		}
		return array_unique($subResults);
	}

	/**
	 * @return Query
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function querySchedulesForAllUsers()
	{
		return $this->getActiveSchedulesQuery()->addSelect('ID')->where('IS_FOR_ALL_USERS', true);
	}

	public function isScheduleForAllUsers($scheduleId)
	{
		$scheduleId = (int)$scheduleId;
		static $schedulesForAllUsers = [];
		if (!array_key_exists($scheduleId, $schedulesForAllUsers))
		{
			$schedulesForAllUsers[$scheduleId] = $this->getActiveSchedulesQuery()
				->addSelect('ID')
				->where('ID', $scheduleId)
				->where('IS_FOR_ALL_USERS', true)
				->exec()
				->fetch();
		}
		return $schedulesForAllUsers[$scheduleId] !== false;
	}

	/**
	 * @param $userId
	 * @param array $options
	 * @return array|Schedule[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findSchedulesByUserId($userId, $options = [])
	{
		$userScheduleMap = $this->findSchedulesByEntityCodes(['U' . $userId], $options);
		return empty($userScheduleMap) ? [] : reset($userScheduleMap);
	}

	public function findSchedulesCollectionByUserId($userId)
	{
		return ScheduleCollection::createFromArray($this->findSchedulesByUserId($userId));
	}

	/**
	 * @param $entityCodesParams
	 * @return Schedule[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findSchedulesByEntityCodes($entityCodesParams, $options = [])
	{
		$userIdsParams = EntityCodesHelper::extractUserIdsFromEntityCodes($entityCodesParams);
		$departmentIdsParams = EntityCodesHelper::extractDepartmentIdsFromEntityCodes($entityCodesParams);
		$allDepartmentIdsForEntities = $departmentIdsParams;
		$assignments = [];
		$entitiesPriorityTree = [];

		if (!empty($userIdsParams))
		{
			foreach ($userIdsParams as $userIdForSearch)
			{
				$entitiesPriorityTree['U' . $userIdForSearch] = $this->departmentRepository->buildUserDepartmentsPriorityTrees($userIdForSearch);
				$allDepartmentIdsForEntities = array_merge($allDepartmentIdsForEntities, $this->departmentRepository->getAllUserDepartmentIds($userIdForSearch));
			}
			$userAssignments = $this->findUserAssignmentsByIds($userIdsParams);
			foreach ($userAssignments as $userAssignment)
			{
				$assignments[$userAssignment['SCHEDULE_ID']]['U' . $userAssignment['USER_ID']] = (int)$userAssignment['STATUS'];
			}
		}
		if (!empty($departmentIdsParams))
		{
			foreach ($departmentIdsParams as $departmentIdForSearch)
			{
				$entitiesPriorityTree['DR' . $departmentIdForSearch] = [$this->departmentRepository->buildDepartmentsPriorityTree($departmentIdForSearch)];
				$allDepartmentIdsForEntities = array_merge($allDepartmentIdsForEntities, $this->departmentRepository->getAllParentDepartmentsIds($departmentIdForSearch));
			}
		}

		$allDepartmentIdsForEntities = array_unique($allDepartmentIdsForEntities);
		if (!empty($allDepartmentIdsForEntities))
		{
			$departmentsAssignments = $this->findDepartmentAssignmentsByIds($allDepartmentIdsForEntities);
			foreach ($departmentsAssignments as $assignment)
			{
				$assignments[$assignment['SCHEDULE_ID']]['DR' . $assignment['DEPARTMENT_ID']] = (int)$assignment['STATUS'];
			}
		}

		$commonScheduleId = $this->findFirstScheduleIdForAllUsers();
		if ($commonScheduleId)
		{
			$firstTree = reset($entitiesPriorityTree);
			if (is_array($firstTree))
			{
				$secondTree = reset($firstTree);
				if (is_array($secondTree))
				{
					$assignments[$commonScheduleId][end($secondTree)] = ScheduleDepartmentTable::INCLUDED;
				}
			}
		}

		$entityToSchedulesMap = [];
		foreach ($assignments as $scheduleId => $scheduleAssignData)
		{
			foreach ($entitiesPriorityTree as $entityAssignCode => $departmentsPriorityTreeData)
			{
				foreach ($departmentsPriorityTreeData as $priorityTree)
				{
					foreach ($priorityTree as $assignCode)
					{
						if (array_key_exists($assignCode, $scheduleAssignData))
						{
							if ($scheduleAssignData[$assignCode] === ScheduleDepartmentTable::EXCLUDED)
							{
								break;
							}
							$entityToSchedulesMap[$entityAssignCode][$scheduleId] = (int)$scheduleId;
						}
					}

				}
			}
		}
		$uniqueScheduleIds = empty($entityToSchedulesMap) ? [] : array_unique(array_merge(...array_values($entityToSchedulesMap)));
		if (empty($uniqueScheduleIds))
		{
			return [];
		}

		$schedules = $this->findSchedulesByIdsForEntity($uniqueScheduleIds, empty($options['select']) ? [] : $options['select']);

		$res = [];
		foreach ($entityToSchedulesMap as $entityMapCode => $schedulesIdsOfEntity)
		{
			foreach ($schedules as $schedule)
			{
				if (in_array((int)$schedule['ID'], $schedulesIdsOfEntity, true))
				{
					$res[$entityMapCode][$schedule['ID']] = $schedule;
				}
			}
		}
		foreach ($entityCodesParams as $entityCode)
		{
			if (($res[$entityCode] ?? null) === null)
			{
				$res[$entityCode] = [];
			}
		}
		return $res;
	}

	public function findDepartment($scheduleId, $depId)
	{
		return ScheduleDepartmentTable::getByPrimary([
			'SCHEDULE_ID' => $scheduleId,
			'DEPARTMENT_ID' => $depId,
		])->fetch();
	}

	public function addDepartment($scheduleId, $depId, $excluded = null)
	{
		if ($excluded === null)
		{
			$excluded = ScheduleDepartmentTable::INCLUDED;
		}
		return ScheduleDepartmentTable::add([
			'SCHEDULE_ID' => $scheduleId,
			'DEPARTMENT_ID' => $depId,
			'STATUS' => $excluded,
		]);
	}

	public function excludeDepartment($scheduleId, $depId)
	{
		$assignment = $this->findDepartment($scheduleId, $depId);
		if ($assignment === false)
		{
			return $this->addDepartment($scheduleId, $depId, ScheduleDepartmentTable::EXCLUDED);
		}
		else
		{
			if ($assignment['STATUS'] == ScheduleDepartmentTable::INCLUDED)
			{
				return ScheduleDepartmentTable::update(
					[
						'SCHEDULE_ID' => $scheduleId,
						'DEPARTMENT_ID' => $depId,
					],
					[
						'STATUS' => ScheduleDepartmentTable::EXCLUDED,
					]
				);
			}
		}
		return (new Result());
	}

	public function findUserAssignmentsByIds($userIds, $exceptScheduleId = null)
	{
		if (empty($userIds))
		{
			return [];
		}
		$result = ScheduleUserTable::query()
			->addSelect('*')
			->registerRuntimeField(new Reference('SCHEDULE', ScheduleTable::class, ['this.SCHEDULE_ID' => 'ref.ID']))
			->where('SCHEDULE.DELETED', ScheduleTable::DELETED_NO)
			->whereIn('USER_ID', $userIds)
		;

		if ($exceptScheduleId > 0)
		{
			$result->whereNot('SCHEDULE_ID', $exceptScheduleId);
		}

		$result->setCacheTtl(3600 * 12);
		$result->cacheJoins(true);

		return $result->exec()->fetchCollection();
	}

	public function findDepartmentAssignmentsByIds($departmentIds, $exceptScheduleId = null)
	{
		if (empty($departmentIds))
		{
			return [];
		}
		$departmentAssignmentsResult = ScheduleDepartmentTable::query()
			->addSelect('*')
			->registerRuntimeField(new Reference('SCHEDULE', ScheduleTable::class, ['this.SCHEDULE_ID' => 'ref.ID']))
			->whereIn('DEPARTMENT_ID', $departmentIds)
			->where('SCHEDULE.DELETED', ScheduleTable::DELETED_NO);
		if ($exceptScheduleId > 0)
		{
			$departmentAssignmentsResult->whereNot('SCHEDULE_ID', $exceptScheduleId);
		}
		$departmentAssignmentsResult->setCacheTtl(3600 * 12);
		$departmentAssignmentsResult->cacheJoins(true);
		return $departmentAssignmentsResult
			->exec()
			->fetchCollection();
	}

	public function findSchedulesForAllUsers($exceptScheduleId = null)
	{
		$res = $this->getActiveSchedulesQuery()
			->addSelect('*')
			->where('IS_FOR_ALL_USERS', true);
		if ($exceptScheduleId !== null)
		{
			$res->whereNot('ID', $exceptScheduleId);
		}
		return $res->exec()
			->fetchAll();
	}

	/**
	 * @return Query|\Bitrix\Timeman\Model\Schedule\EO_Schedule_Query
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getActiveSchedulesQuery()
	{
		return ScheduleTable::query()->where('DELETED', ScheduleTable::DELETED_NO);
	}

	protected function findFirstScheduleIdForAllUsers()
	{
		$res = $this->getActiveSchedulesQuery()
			->addSelect('ID')
			->where('IS_FOR_ALL_USERS', true)
			->setCacheTtl(3600 * 12)
			->exec()
			->fetch();
		if ($res)
		{
			return $res['ID'];
		}
		return null;
	}

	public function updateIsForAllUsers(Schedule $schedule)
	{
		$schedules = ScheduleTable::query()
			->addSelect('ID')
			->where('IS_FOR_ALL_USERS', true)
			->whereNot('ID', $schedule->getId())
			->exec()
			->fetchCollection();
		if ($schedules->count() === 0)
		{
			return;
		}
		ScheduleTable::updateMulti(
			$schedules->getIdList(),
			['IS_FOR_ALL_USERS' => false]
		);
	}

	protected function findSchedulesByIdsForEntity($userScheduleIds, $fieldsToSelect = [])
	{
		$query = $this->getActiveSchedulesQuery();
		if (empty($fieldsToSelect))
		{
			$query->addSelect('*')
				->addSelect('DEPARTMENTS')
				->addSelect('SCHEDULE_VIOLATION_RULES')
				->addSelect('SHIFTS')
				->registerRuntimeField((new OneToMany('DEPARTMENTS', Department\ScheduleDepartmentTable::class, 'SCHEDULE'))->configureJoinType('LEFT'))
				->whereIn('ID', $userScheduleIds)
				->where(Query::filter()->logic('or')
					->where('SHIFTS.DELETED', ShiftTable::DELETED_NO)
					->whereNull('SHIFTS.DELETED')
				);
		}
		else
		{
			$query->addSelect('ID');
			foreach ($fieldsToSelect as $fieldName)
			{
				$query->addSelect($fieldName);
			}
		}
		$query->setCacheTtl(3600 * 12);
		$query->cacheJoins(true);
		return $query
			->whereIn('ID', $userScheduleIds)
			->exec()
			->fetchCollection();
	}

	/**
	 * @return DepartmentRepository
	 */
	public function getDepartmentRepository()
	{
		return $this->departmentRepository;
	}

	/**
	 * @param \Bitrix\Main\ORM\Query\Filter\ConditionTree $filter
	 */
	public function findAllBy($selectFields, $filter, $limit = null)
	{
		$query = $this->getActiveSchedulesQuery();
		foreach ($selectFields as $selectField)
		{
			$query->addSelect($selectField);
		}
		if ($limit > 0)
		{
			$query->setLimit($limit);
		}
		return $query->where($filter)
			->exec()
			->fetchCollection();
	}
}