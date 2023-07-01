<?php
namespace Bitrix\Timeman\Provider\Schedule;

use Bitrix\Main\EO_User_Collection;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Model\Schedule\Assignment\Department\ScheduleDepartment;
use Bitrix\Timeman\Model\Schedule\Assignment\User\ScheduleUser;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\User\UserCollection;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;

class ScheduleProvider extends ScheduleRepository
{
	private $schedulesByUserIds = [];
	private $schedulesWithShifts = [];

	/**
	 * @param $userId
	 * @param array $options
	 * @return Schedule[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findSchedulesByUserId($userId, $options = [])
	{
		$key = $this->buildCachedKeyForScheduleByUser($userId, $options);
		if (!isset($this->schedulesByUserIds[$key]))
		{
			if ($existedKey = $this->getKeyForDataWithEnoughFields($userId, $options))
			{
				return $this->schedulesByUserIds[$existedKey];
			}
			$this->schedulesByUserIds[$key] = parent::findSchedulesByUserId($userId, $options);
		}
		return $this->schedulesByUserIds[$key];
	}

	private function buildCachedKeyForScheduleByUser($userId, $options)
	{
		$key = $userId;
		if (is_array($options) && isset($options['select']) && is_array($options['select']))
		{
			foreach ($options['select'] as $fieldToSelect)
			{
				$key .= '_' . $fieldToSelect;
			}
		}
		return $key;
	}

	private function getKeyForDataWithEnoughFields($userId, $options)
	{
		if (isset($this->schedulesByUserIds[$userId]))
		{
			if (is_array($options) && isset($options['select']) && is_array($options['select']))
			{
				foreach ($options['select'] as $fieldToSelect)
				{
					foreach ($this->schedulesByUserIds[$userId] as $schedule)
					{
						try
						{
							if (!$schedule->has($fieldToSelect))
							{
								return null;
							}
						}
						catch (\Exception $exc)
						{
							return null;
						}
					}
				}
			}

			return $userId;
		}
		return null;
	}

	public function getScheduleWithShifts($scheduleId)
	{
		if ($scheduleId <= 0)
		{
			return null;
		}
		if (!isset($this->schedulesWithShifts[$scheduleId]))
		{
			$this->schedulesWithShifts[$scheduleId] = parent::findByIdWithShifts($scheduleId);
			if ($this->schedulesWithShifts[$scheduleId] === null)
			{
				$this->schedulesWithShifts[$scheduleId] = false;
			}
		}
		return $this->schedulesWithShifts[$scheduleId] === false ? null : $this->schedulesWithShifts[$scheduleId];
	}

	/**
	 * @param ScheduleUser[] $userAssignments
	 * @param ScheduleDepartment[] $departmentAssignments
	 */
	public function buildUserToDepartmentsMapByAssignments($userAssignments, $departmentAssignments)
	{
		$userToDepartmentsMap = [];
		$excludedDepartmentCodes = [];
		$excludedUserCodes = [];
		$departmentRepository = parent::getDepartmentRepository();
		foreach ($userAssignments as $userAssignment)
		{
			$userId = $userAssignment->getUserId();
			if ($userAssignment->isIncluded())
			{
				if (!array_key_exists($userId, $userToDepartmentsMap))
				{
					$userToDepartmentsMap[EntityCodesHelper::buildUserCode($userId)] = [];
				}
				$userToDepartmentsMap[EntityCodesHelper::buildUserCode($userId)] = EntityCodesHelper::buildDepartmentCodes(
					$departmentRepository->getDirectParentIdsByUserId($userId)
				);
			}
			elseif ($userAssignment->isExcluded())
			{
				$excludedUserCodes[] = EntityCodesHelper::buildUserCode($userId);
			}
		}
		foreach ($departmentAssignments as $departmentAssignment)
		{
			if ($departmentAssignment->isExcluded())
			{
				$excludedDepartmentCodes[] = EntityCodesHelper::buildDepartmentCode($departmentAssignment->getDepartmentId());
			}
			elseif ($departmentAssignment->isIncluded())
			{
				$childDepartments = $departmentRepository->getAllChildDepartmentsIds($departmentAssignment->getDepartmentId());
				$childDepartments[] = $departmentAssignment->getDepartmentId();
				foreach ($childDepartments as $childDepartmentId)
				{
					// manager first
					$managerId = $departmentRepository->getDepartmentManagerId($childDepartmentId);
					$userToDepartmentsMap[EntityCodesHelper::buildUserCode($managerId)][] = EntityCodesHelper::buildDepartmentCode($childDepartmentId);

					$users = $departmentRepository->getUsersOfDepartment($childDepartmentId);
					foreach ($users as $userId)
					{
						$userCode = EntityCodesHelper::buildUserCode($userId);
						if (!array_key_exists($userCode, $userToDepartmentsMap))
						{
							$userToDepartmentsMap[$userCode] = [];
						}
						$userToDepartmentsMap[$userCode] = array_merge(
							$userToDepartmentsMap[$userCode],
							EntityCodesHelper::buildDepartmentCodes(
								$departmentRepository->getDirectParentIdsByUserId($userId)
							)
						);
					}
				}
			}
		}
		foreach ($userToDepartmentsMap as $index => $userToDepartments)
		{
			$userToDepartmentsMap[$index] = array_unique($userToDepartments);
		}

		//
		foreach ($excludedUserCodes as $excludedUserCode)
		{
			if (isset($userToDepartmentsMap[$excludedUserCode]))
			{
				unset($userToDepartmentsMap[$excludedUserCode]);
			}
		}
		//
		foreach ($userToDepartmentsMap as $userCode => $userDepartmentsCodes)
		{
			foreach ($userDepartmentsCodes as $userDepartmentCodeIndex => $userDepartmentCode)
			{
				$allParents = $departmentRepository->getAllParentDepartmentsIds(EntityCodesHelper::getDepartmentId($userDepartmentCode));
				$allDepCodes = array_merge(EntityCodesHelper::buildDepartmentCodes($allParents), [$userDepartmentCode]);
				if (!empty(array_intersect($excludedDepartmentCodes, $allDepCodes)))
				{
					unset($userToDepartmentsMap[$userCode][$userDepartmentCodeIndex]);
					$userToDepartmentsMap[$userCode] = array_filter($userToDepartmentsMap[$userCode]);
				}
			}

		}
		$userToDepartmentsMap = array_filter($userToDepartmentsMap);
		return $userToDepartmentsMap;
	}

	/**
	 * @param Schedule $schedule
	 * @return int
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getUsersCount($schedule)
	{
		return count($this->findActiveScheduleUserIds($schedule));
	}

	public function findActiveScheduleUserIds($schedule)
	{
		if (!($schedule instanceof Schedule))
		{
			return 0;
		}
		$departments = $schedule->obtainDepartmentAssignments();
		$baseId = parent::getDepartmentRepository()->getBaseDepartmentId();
		if ($schedule->getIsForAllUsers() && !$schedule->obtainDepartmentAssignmentById($baseId))
		{
			$departments[] = (new ScheduleDepartment(false))
				->setDepartmentId($baseId)
				->setScheduleId($schedule->getId())
				->setIsIncluded();
		}
		$map = $this->buildUserToDepartmentsMapByAssignments($schedule->obtainUserAssignments()->getAll(), $departments->getAll());
		return EntityCodesHelper::extractUserIdsFromEntityCodes(array_keys($map));
	}

	/**
	 * @param Schedule $schedule
	 * @return UserCollection
	 */
	public function findActiveUsers(Schedule $schedule)
	{
		$ids = $this->findActiveScheduleUserIds($schedule);
		if (empty($ids))
		{
			return new UserCollection();
		}
		return parent::getUsersBaseQuery()
			->whereIn('ID', $ids)
			->exec()
			->fetchCollection();
	}
}