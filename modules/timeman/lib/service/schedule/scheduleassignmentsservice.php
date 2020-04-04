<?php
namespace Bitrix\Timeman\Service\Schedule;

use Bitrix\Main\Result;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUserTable;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Repository\DepartmentRepository;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Service\BaseService;
use Bitrix\Timeman\Service\Schedule\Result\ScheduleServiceResult;

/**
 * Class ScheduleAssignmentsService - class for internal needs.
 * Do not use it directly, it makes no sense
 * @package Bitrix\Timeman\Services\Schedule
 */
final class ScheduleAssignmentsService extends BaseService
{
	/** @var ScheduleRepository */
	private $scheduleRepository;
	/** @var ShiftRepository */
	private $shiftRepository;
	/** @var ShiftPlanRepository */
	private $shiftPlanRepository;
	/** @var DepartmentRepository */
	private $departmentRepository;

	public function __construct(
		ScheduleRepository $scheduleRepository,
		ShiftRepository $shiftRepository,
		ShiftPlanRepository $shiftPlanRepository,
		DepartmentRepository $departmentRepository
	)
	{
		$this->scheduleRepository = $scheduleRepository;
		$this->shiftRepository = $shiftRepository;
		$this->shiftPlanRepository = $shiftPlanRepository;
		$this->departmentRepository = $departmentRepository;
	}

	/**
	 * @param $scheduleId
	 * @param ScheduleForm $scheduleForm
	 * @return \Bitrix\Timeman\Service\BaseServiceResult
	 */
	public function addUserAssignment($scheduleId, $userIds)
	{
		$scheduleForm = new ScheduleForm();
		$scheduleForm->id = $scheduleId;
		$scheduleForm->userIds = $userIds;

		if (!$scheduleForm->validate(['userIds', 'scheduleId']))
		{
			return (new ScheduleServiceResult())->addError($scheduleForm->getFirstError());
		}

		return $this->wrapAction(function () use ($scheduleId, $scheduleForm) {
			return $this->insertUsersAssignment($scheduleId, $scheduleForm->userIds, ScheduleUserTable::INCLUDED);
		});
	}

	/**
	 * @param $scheduleId
	 * @param ScheduleForm $scheduleForm
	 * @return \Bitrix\Timeman\Service\BaseServiceResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteUserAssignments($scheduleId, $userIds)
	{
		return $this->wrapAction(function () use ($scheduleId, $userIds) {

			$scheduleForm = new ScheduleForm();
			$scheduleForm->id = $scheduleId;
			$scheduleForm->userIdsExcluded = $userIds;

			if (!$scheduleForm->validate(['userIdsExcluded', 'scheduleId']))
			{
				return (new ScheduleServiceResult())->addError($scheduleForm->getFirstError());
			}

			$this->safeRun($this->insertUsersAssignment($scheduleId, $scheduleForm->userIdsExcluded, ScheduleUserTable::EXCLUDED));

			$resDelete = $this->safeRun($this->deleteShiftPlansOfNotActiveUsers($scheduleId));

			return $this->buildResultWithSchedule($resDelete->getSchedule());
		});
	}

	private function buildResultWithSchedule($schedule)
	{
		/** @var Schedule $schedule */
		$schedule->defineUsersCount(
			$this->getScheduleRepository()->getUsersCount($schedule->getId(), $schedule->obtainDepartmentAssignments())
		);
		return (new ScheduleServiceResult())
			->setSchedule($schedule);
	}

	private function insertUsersAssignment($scheduleId, $userIds, $excluded)
	{
		$schedule = $scheduleId;
		if (!($schedule instanceof Schedule))
		{
			$schedule = $this->findScheduleWithUsers($scheduleId);
		}
		if (!$schedule)
		{
			return (new ScheduleServiceResult())->addScheduleNotFoundError();
		}

		$insertData = [];
		$updated = [];
		foreach ($userIds as $userId)
		{
			$user = $schedule->obtainFromAssignments($userId);
			if (!$user)
			{
				$insertData[] = ScheduleUser::create(
					$scheduleId,
					$userId,
					$excluded
				);
			}
			elseif ($user->getStatus() != $excluded)
			{
				$updated[] = $userId;
			}
		}

		$this->safeRun($this->getScheduleRepository()->addBatchUsers($insertData));

		$this->safeRun($this->getScheduleRepository()->updateBatchUsers($scheduleId, $updated, $excluded));

		return $this->buildResultWithSchedule($schedule);
	}

	private function findScheduleWithUsers($scheduleId)
	{
		return $this->getScheduleRepository()->findByIdWith((int)$scheduleId, ['USER_ASSIGNMENTS', 'DEPARTMENT_ASSIGNMENTS', 'ACTIVE_USERS', 'EXCLUDED_USERS']);
	}

	/**
	 * @param $scheduleOrId
	 * @param ScheduleForm $scheduleForm
	 * @return ScheduleServiceResult
	 */
	public function saveAssignments($scheduleOrId, ScheduleForm $scheduleForm)
	{
		$schedule = $scheduleOrId;
		if (!($schedule instanceof Schedule))
		{
			$schedule = $this->findScheduleWithUsers($scheduleOrId);
		}
		if (!$schedule)
		{
			return (new ScheduleServiceResult())->addScheduleNotFoundError();
		}
		$scheduleId = $schedule->getId();
		if ($scheduleForm->isForAllUsers === true && $schedule->getIsForAllUsers() !== true)
		{
			$this->safeRun(ScheduleTable::update($scheduleId, ['IS_FOR_ALL_USERS' => true]));
		}
		$oldAssignmentsForm = new ScheduleForm($schedule);

		# USERS
		$deleteUserIds = array_values(
			array_diff(
				array_merge($oldAssignmentsForm->userIds, $oldAssignmentsForm->userIdsExcluded),
				array_merge($scheduleForm->userIds, $scheduleForm->userIdsExcluded)
			)
		);
		$insertUserIds = array_values(
			array_diff(
				array_merge($scheduleForm->userIds, $scheduleForm->userIdsExcluded),
				array_merge($oldAssignmentsForm->userIds, $oldAssignmentsForm->userIdsExcluded)
			)
		);
		$updateUserIds = array_values(
			array_merge(
				array_intersect(
					$scheduleForm->userIdsExcluded,
					$oldAssignmentsForm->userIds
				),
				array_intersect(
					$scheduleForm->userIds,
					$oldAssignmentsForm->userIdsExcluded
				)
			)
		);
		# delete
		$this->safeRun($this->getScheduleRepository()->deleteUsersAssignments($scheduleId, $deleteUserIds));

		# insert
		$batchInsertUsersData = [];
		foreach ($insertUserIds as $insertUserId)
		{
			$batchInsertUsersData[] = ScheduleUser::create(
				$schedule->getId(),
				$insertUserId,
				(int)in_array($insertUserId, $scheduleForm->userIdsExcluded)
			);
		}
		$this->safeRun($this->getScheduleRepository()->addBatchUsers($batchInsertUsersData));

		# update
		$userIdsToInclude = $userIdsToExclude = [];
		foreach ($updateUserIds as $updateUserId)
		{
			if (in_array($updateUserId, $scheduleForm->userIdsExcluded))
			{
				$userIdsToExclude[] = $updateUserId;
			}
			else
			{
				$userIdsToInclude[] = $updateUserId;
			}
		}
		$this->safeRun($this->getScheduleRepository()->updateBatchUsers($scheduleId, $userIdsToInclude, ScheduleUserTable::INCLUDED));
		$this->safeRun($this->getScheduleRepository()->updateBatchUsers($scheduleId, $userIdsToExclude, ScheduleUserTable::EXCLUDED));


		# DEPARTMENTS
		$deleteDepartmentIds = array_diff(
			array_merge($oldAssignmentsForm->departmentIds, $oldAssignmentsForm->departmentIdsExcluded),
			array_merge($scheduleForm->departmentIds, $scheduleForm->departmentIdsExcluded)
		);
		$insertDepartmentIds = array_diff(
			array_merge($scheduleForm->departmentIds, $scheduleForm->departmentIdsExcluded),
			array_merge($oldAssignmentsForm->departmentIds, $oldAssignmentsForm->departmentIdsExcluded)
		);
		$updateDepartmentIds = array_merge(
			array_intersect(
				$scheduleForm->departmentIdsExcluded,
				$oldAssignmentsForm->departmentIds
			),
			array_intersect(
				$scheduleForm->departmentIds,
				$oldAssignmentsForm->departmentIdsExcluded
			)
		);
		# delete
		$this->safeRun($this->getScheduleRepository()->deleteDepartmentsAssignments($scheduleId, $deleteDepartmentIds));

		# insert
		$batchInsertDepartmentsData = [];
		foreach ($insertDepartmentIds as $insertDepartmentId)
		{
			$item = new ScheduleDepartment(false);
			$item->setScheduleId($schedule->getId());
			$item->setDepartmentId($insertDepartmentId);
			$item->setStatus((int)in_array($insertDepartmentId, $scheduleForm->departmentIdsExcluded));
			$batchInsertDepartmentsData[] = $item;
		}
		$this->safeRun($this->getScheduleRepository()->addBatchDepartments($batchInsertDepartmentsData));

		# update
		$departmentsToInclude = $departmentsToExclude = [];
		foreach ($updateDepartmentIds as $updateDepartmentId)
		{
			if (in_array($updateDepartmentId, $scheduleForm->departmentIdsExcluded))
			{
				$departmentsToExclude[] = $updateDepartmentId;
			}
			else
			{
				$departmentsToInclude[] = $updateDepartmentId;
			}
		}
		$this->safeRun($this->getScheduleRepository()->updateBatchDepartments($scheduleId, $departmentsToInclude, 0));
		return $this->safeRun($this->getScheduleRepository()->updateBatchDepartments($scheduleId, $departmentsToExclude, 1));
	}

	public function deleteShiftPlansOfNotActiveUsers($scheduleId)
	{
		if (!($schedule = $this->findScheduleWithUsers($scheduleId)))
		{
			return (new ScheduleServiceResult())->addScheduleNotFoundError();
		}

		$shiftIds = $this->getShiftRepository()->findShiftIdsBySchedule($schedule->getId());
		$activeUserIds = $schedule->obtainActiveUserIds();

		if (empty($shiftIds))
		{
			return (new ScheduleServiceResult())->setSchedule($schedule);
		}

		if (empty($activeUserIds))
		{
			$this->getShiftPlanRepository()->deleteByShiftIds($shiftIds);
		}
		else
		{
			$this->getShiftPlanRepository()->deleteByShiftAndNotUsersIds($shiftIds, $activeUserIds);
		}
		return (new ScheduleServiceResult())->setSchedule($schedule);
	}

	public function excludeDepartments($scheduleId, $depIds)
	{
		return $this->wrapAction(function () use ($scheduleId, $depIds) {
			foreach ($depIds as $depId)
			{
				$this->safeRun(
					$this->getScheduleRepository()->excludeDepartment($scheduleId, $depId)
				);
			}
			return new ScheduleServiceResult();
		});
	}

	public function findSchedulesForDepartments($departmentIds, $exceptScheduleId)
	{
		if (empty($departmentIds))
		{
			return [];
		}

		$departmentsRelationsData = $this->fetchDepartmentRelationsData($departmentIds);
		$allDepIds = $this->getAllDepartmentsIds($departmentsRelationsData);
		if (empty($allDepIds))
		{
			return new Result();
		}
		$schedulesForAllUsers = $this->getScheduleRepository()
			->findSchedulesForAllUsers($exceptScheduleId);
		$results = $this->fillCommonSchedulesAssignments($departmentIds, $schedulesForAllUsers);

		$scheduleDepartmentsResult = $this->getScheduleRepository()
			->findDepartmentAssignmentsByIds($allDepIds, $exceptScheduleId);

		$departmentAssignmentsResult = [];
		foreach ($scheduleDepartmentsResult as $item)
		{
			$departmentAssignmentsResult[$item['DEPARTMENT_ID']][] = $item;
		}
		$commonSchedulesIds = array_map('intval', array_column($schedulesForAllUsers, 'ID'));

		foreach ($departmentsRelationsData as $depId => $departmentsChainData)
		{
			$results = $this->fillDepartmentsAssignments($departmentsChainData, $departmentAssignmentsResult, $results, $depId, $commonSchedulesIds);
			if (!empty($results[$depId]))
			{
				$results[$depId] = array_values(array_filter(array_unique($results[$depId])));
			}
		}
		return $results;
	}

	private function fetchDepartmentRelationsData($departmentIds)
	{
		$departmentsRelationsData = [];
		foreach ($departmentIds as $depId)
		{
			$departmentsRelationsData[$depId] = $this->getDepartmentRepository()
				->findDepartmentsChain($depId);
		}
		return $departmentsRelationsData;
	}

	private function getAllDepartmentsIds($departmentsRelationsData)
	{
		$allDepIds = [];
		foreach ($departmentsRelationsData as $departmentsRelationsDatum)
		{
			foreach ($departmentsRelationsDatum as $data)
			{
				$allDepIds[] = (int)$data['ID'];
			}
		}
		return array_unique($allDepIds);
	}

	private function fillCommonSchedulesAssignments($departmentIds, $schedulesForAllUsers)
	{
		$results = [];

		foreach ($schedulesForAllUsers as $scheduleForAllUser)
		{
			foreach ($departmentIds as $departmentId)
			{
				$results[$departmentId][] = (int)$scheduleForAllUser['ID'];
			}
		}
		return $results;
	}

	private function removeExcludedAssignFromResults($depId, $excludedScheduleId, $commonSchedulesIds, &$results)
	{
		if (in_array($excludedScheduleId, $commonSchedulesIds, true))
		{
			$results[$depId] = array_filter($results[$depId], function ($value) use ($excludedScheduleId) {
				return $value !== $excludedScheduleId;
			});
			if (empty($results[$depId]))
			{
				unset($results[$depId]);
			}
		}
	}

	/**
	 * @param $departmentsChainData
	 * @param array $departmentAssignments
	 * @param array $results
	 * @param $depId
	 * @param array $commonSchedulesIds
	 * @return array
	 */
	private function fillDepartmentsAssignments($departmentsChainData, $departmentAssignments, $results, $depId, $commonSchedulesIds)
	{
		$stopOnFirstExcludedParent = [];
		foreach ($departmentsChainData as $depDatum)
		{
			if (isset($departmentAssignments[$depDatum['ID']]))
			{
				foreach ($departmentAssignments[$depDatum['ID']] as $assignData)
				{
					$schId = (int)$assignData['SCHEDULE_ID'];
					if ($stopOnFirstExcludedParent[$depId . '-' . $schId] === true)
					{
						continue;
					}
					if (ScheduleDepartment::isDepartmentIncluded($assignData))
					{
						$selfExcluded = false;
						if (isset($departmentAssignments[$depId]))
						{
							foreach ($departmentAssignments[$depId] as $selfAssignData)
							{
								if ($schId === (int)$selfAssignData['SCHEDULE_ID'] &&
									ScheduleDepartment::isDepartmentExcluded($selfAssignData))
								{
									$selfExcluded = true;
									break;
								}
							}
						}

						if (!$selfExcluded)
						{
							$results[$depId][] = $schId;
						}
					}
					else
					{
						$this->removeExcludedAssignFromResults($depId, $schId, $commonSchedulesIds, $results);
						$stopOnFirstExcludedParent[$depId . '-' . $schId] = true;
					}
				}
			}
		}
		return $results;
	}

	private function getScheduleRepository()
	{
		return $this->scheduleRepository;
	}

	private function getShiftRepository()
	{
		return $this->shiftRepository;
	}

	private function getShiftPlanRepository()
	{
		return $this->shiftPlanRepository;
	}

	private function getDepartmentRepository()
	{
		return $this->departmentRepository;
	}
}