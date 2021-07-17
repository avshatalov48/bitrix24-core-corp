<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Processor;

use Bitrix\Main;
use Bitrix\Tasks\Internals\Counter\EffectiveTable;
use Bitrix\Tasks\Internals\Counter\Event\EventCollection;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\Counter\Event\EventResource;
use Bitrix\Tasks\Internals\Counter\Event\EventResourceCollection;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Util\Type\DateTime;

class EfficiencyProcessor
{
	private $originData = [];
	private $modifiedData = [];


	public function __construct()
	{
		$resourceCollection = EventResourceCollection::getInstance();
		$this->originData = $resourceCollection->getOrigin();
		$this->modifiedData = $resourceCollection->getModified();
	}

	/**
	 * @param array $ids
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function recount(array $ids)
	{
		$eventCollection = EventCollection::getInstance();
		$deletedTasks = $eventCollection->getTasksByEventType(EventDictionary::EVENT_AFTER_TASK_DELETE);
		$addedTasks = $eventCollection->getTasksByEventType(EventDictionary::EVENT_AFTER_TASK_ADD);
		$restoredTasks = $eventCollection->getTasksByEventType(EventDictionary::EVENT_AFTER_TASK_RESTORE);
		$expiredTasks = $eventCollection->getTasksByEventType(EventDictionary::EVENT_TASK_EXPIRED);

		$processedUsers = [];

		foreach ($ids as $taskId)
		{
			if (in_array($taskId, $deletedTasks, true))
			{
				$processedUsers[] = $this->updateEfficiencyForDeletedAndAdded($taskId, true);
			}
			elseif (in_array($taskId, $addedTasks, true))
			{
				$processedUsers[] = $this->updateEfficiencyForDeletedAndAdded($taskId);
			}
			elseif (in_array($taskId, $restoredTasks, true))
			{
				$processedUsers[] = $this->updateEfficiencyForRestored($taskId);
			}
			elseif (in_array($taskId, $expiredTasks, true))
			{
				$processedUsers[] = $this->updateEfficiencyForExpired($taskId);
			}
			else
			{
				$processedUsers[] = $this->updateEfficiencyForUpdated($taskId);
			}
		}

		$processedUsers = array_unique(array_merge(...$processedUsers));
		foreach ($processedUsers as $userId)
		{
			Effective::recountEfficiencyUserCounter($userId);
		}
	}

	/**
	 * @param int $taskId
	 * @param bool $isDeleted
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function updateEfficiencyForDeletedAndAdded(int $taskId, bool $isDeleted = false): array
	{
		/** @var EventResource $task */
		$task = ($this->modifiedData[$taskId] ?? $this->originData[$taskId]);
		if (!$task)
		{
			return [];
		}

		$taskMembers = $task->getMembersAsArray();

		$membersMap = array_fill_keys(
			$taskMembers[MemberTable::MEMBER_TYPE_ACCOMPLICE],
			MemberTable::MEMBER_TYPE_ACCOMPLICE
		);
		$membersMap[current($taskMembers[MemberTable::MEMBER_TYPE_RESPONSIBLE])] = MemberTable::MEMBER_TYPE_RESPONSIBLE;

		$taskData = [
			'ID' => $taskId,
			'TITLE' => $task->getTitle(),
			'DEADLINE' => $task->getDeadline(),
			'CREATED_BY' => current($taskMembers[MemberTable::MEMBER_TYPE_ORIGINATOR]),
		];

		$processedMembers = [];
		foreach ($membersMap as $userId => $type)
		{
			Effective::modify($userId, $type, $taskData, $task->getGroupId(), false, false);
			$processedMembers[$userId] = $userId;
		}

		if ($isDeleted)
		{
			Effective::repair($taskId);
		}

		return $processedMembers;
	}

	/**
	 * @param int $taskId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function updateEfficiencyForRestored(int $taskId): array
	{
		/** @var EventResource $task */
		$task = $this->modifiedData[$taskId];
		$taskMembers = $task->getMembersAsArray();

		$membersMap = array_fill_keys(
			$taskMembers[MemberTable::MEMBER_TYPE_ACCOMPLICE],
			MemberTable::MEMBER_TYPE_ACCOMPLICE
		);
		$membersMap[current($taskMembers[MemberTable::MEMBER_TYPE_RESPONSIBLE])] = MemberTable::MEMBER_TYPE_RESPONSIBLE;

		$taskData = [
			'ID' => $taskId,
			'TITLE' => $task->getTitle(),
			'DEADLINE' => $task->getDeadline(),
			'CREATED_BY' => current($taskMembers[MemberTable::MEMBER_TYPE_ORIGINATOR]),
		];

		$processedMembers = [];
		foreach ($membersMap as $userId => $type)
		{
			Effective::modify($userId, $type, $taskData, $task->getGroupId(), $task->isExpired(), false);
			$processedMembers[$userId] = $userId;
		}

		return $processedMembers;
	}

	/**
	 * @param int $taskId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function updateEfficiencyForExpired(int $taskId): array
	{
		/** @var EventResource $task */
		$task = $this->modifiedData[$taskId];
		$taskMembers = $task->getMembersAsArray();

		$membersMap = array_fill_keys(
			$taskMembers[MemberTable::MEMBER_TYPE_ACCOMPLICE],
			MemberTable::MEMBER_TYPE_ACCOMPLICE
		);
		$membersMap[current($taskMembers[MemberTable::MEMBER_TYPE_RESPONSIBLE])] = MemberTable::MEMBER_TYPE_RESPONSIBLE;

		$taskData = [
			'ID' => $taskId,
			'TITLE' => $task->getTitle(),
			'DEADLINE' => $task->getDeadline(),
			'CREATED_BY' => current($taskMembers[MemberTable::MEMBER_TYPE_ORIGINATOR]),
		];

		$processedMembers = [];
		foreach ($membersMap as $userId => $type)
		{
			if (!Effective::checkActiveViolations($taskId, $userId, $task->getGroupId()))
			{
				Effective::modify($userId, $type, $taskData, $task->getGroupId(), true, false);
				$processedMembers[$userId] = $userId;
			}
		}

		return $processedMembers;
	}

	/**
	 * @param int $taskId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	private function updateEfficiencyForUpdated(int $taskId): array
	{
		if (
			!array_key_exists($taskId, $this->originData)
			|| !array_key_exists($taskId, $this->modifiedData)
		)
		{
			return [];
		}

		/** @var EventResource $oldTask */
		$oldTask = $this->originData[$taskId];
		/** @var EventResource $newTask */
		$newTask = $this->modifiedData[$taskId];

		if (
			!$oldTask
			|| !$newTask
		)
		{
			return [];
		}

		$oldStatus = $oldTask->getStatus();
		$newStatus = $newTask->getStatus();
		$statusChanged = $oldStatus !== $newStatus;

		/** @var DateTime $oldDeadline */
		$oldDeadline = $oldTask->getDeadline();
		/** @var DateTime $newDeadline */
		$newDeadline = $newTask->getDeadline();
		$deadlineChanged =
			(!$oldDeadline && $newDeadline)
			|| ($oldDeadline && !$newDeadline)
			|| ($oldDeadline && $newDeadline && $oldDeadline->isNotEqualTo($newDeadline))
		;
		$isViolation = ($deadlineChanged ? $newTask->isExpired() : $oldTask->isExpired());

		$oldGroupId = $oldTask->getGroupId();
		$newGroupId = $newTask->getGroupId();
		$groupChanged = $oldGroupId !== $newGroupId;
		$groupId = ($groupChanged ? $newGroupId : $oldGroupId);

		$oldMembers = $oldTask->getMembersAsArray();
		$newMembers = $newTask->getMembersAsArray();

		$oldResponsibleId = current($oldMembers[MemberTable::MEMBER_TYPE_RESPONSIBLE]);
		$newResponsibleId = current($newMembers[MemberTable::MEMBER_TYPE_RESPONSIBLE]);
		$responsibleChanged = $oldResponsibleId !== $newResponsibleId;

		$oldAccomplices = $oldMembers[MemberTable::MEMBER_TYPE_ACCOMPLICE];
		$newAccomplices = $newMembers[MemberTable::MEMBER_TYPE_ACCOMPLICE];
		$accomplicesIn = array_diff($newAccomplices, $oldAccomplices);
		$accomplicesOut = array_diff($oldAccomplices, $newAccomplices);
		$allAccomplices = array_unique(array_merge($oldAccomplices, $newAccomplices));
		$accomplicesChanged = $oldAccomplices !== $newAccomplices;

		$oldTaskData = [
			'ID' => $taskId,
			'TITLE' => $newTask->getTitle(),
			'DEADLINE' => $newTask->getDeadline(),
			'CREATED_BY' => current($oldMembers[MemberTable::MEMBER_TYPE_ORIGINATOR]),
		];
		$newTaskData = [
			'ID' => $taskId,
			'TITLE' => $newTask->getTitle(),
			'DEADLINE' => $newTask->getDeadline(),
			'CREATED_BY' => current($newMembers[MemberTable::MEMBER_TYPE_ORIGINATOR]),
		];

		$responsibleModified = false;
		$accomplicesModified = false;

		$canProceed = false;
		$statesCompleted = [\CTasks::STATE_DEFERRED, \CTasks::STATE_SUPPOSEDLY_COMPLETED, \CTasks::STATE_COMPLETED];
		$statesInProgress = [\CTasks::STATE_NEW, \CTasks::STATE_PENDING, \CTasks::STATE_IN_PROGRESS];

		$processedMembers = [];

		// TASK DEFERRED OR COMPLETED
		if ($statusChanged && in_array($newStatus, $statesCompleted, true))
		{
			Effective::repair($taskId);
			$this->modifyEfficiencyForResponsible($oldResponsibleId, $oldTaskData, $oldGroupId, false);
			$processedMembers[$oldResponsibleId] = $oldResponsibleId;

			foreach ($oldAccomplices as $userId)
			{
				if ($userId !== $oldResponsibleId)
				{
					$this->modifyEfficiencyForAccomplice($userId, $oldTaskData, $oldGroupId, false);
					$processedMembers[$userId] = $userId;
				}
			}

			if ($responsibleChanged)
			{
				$this->modifyEfficiencyForResponsible($newResponsibleId, $newTaskData, $groupId, false);
				$processedMembers[$newResponsibleId] = $newResponsibleId;
			}
			if ($accomplicesChanged)
			{
				foreach ($accomplicesIn as $userId)
				{
					if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
					{
						$this->modifyEfficiencyForAccomplice($userId, $newTaskData, $groupId, false);
						$processedMembers[$userId] = $userId;
					}
				}
			}

			return $processedMembers;
		}

		// TASK RESTARTED
		if (
			$statusChanged
			&& in_array($oldStatus, $statesCompleted, true)
			&& in_array($newStatus, $statesInProgress, true)
		)
		{
			if (!$responsibleChanged)
			{
				$this->modifyEfficiencyForResponsible($oldResponsibleId, $oldTaskData, $groupId, $isViolation);
				$processedMembers[$oldResponsibleId] = $oldResponsibleId;
				$responsibleModified = true;
			}
			if (!$accomplicesChanged)
			{
				foreach ($oldAccomplices as $userId)
				{
					if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
					{
						$this->modifyEfficiencyForAccomplice($userId, $oldTaskData, $groupId, $isViolation);
						$processedMembers[$userId] = $userId;
					}
				}
				$accomplicesModified = true;
			}

			$canProceed = true;
		}

		if (!$canProceed && in_array($oldStatus, $statesCompleted, true))
		{
			return $processedMembers;
		}

		// RESPONSIBLE CHANGED
		if ($responsibleChanged)
		{
			if (
				($activeViolations = Effective::checkActiveViolations($taskId, $oldResponsibleId))
				&& in_array($oldResponsibleId, $newAccomplices, true)
			)
			{
				EffectiveTable::update(
					$activeViolations[0]['ID'],
					['USER_TYPE' => MemberTable::MEMBER_TYPE_ACCOMPLICE, 'GROUP_ID' => $groupId]
				);
			}
			else
			{
				Effective::repair($taskId, $oldResponsibleId, MemberTable::MEMBER_TYPE_RESPONSIBLE);
			}

			$this->modifyEfficiencyForResponsible($oldResponsibleId, $oldTaskData, $oldGroupId, false);
			$processedMembers[$oldResponsibleId] = $oldResponsibleId;

			if ($activeViolations = Effective::checkActiveViolations($taskId, $newResponsibleId))
			{
				EffectiveTable::update(
					$activeViolations[0]['ID'],
					['USER_TYPE' => MemberTable::MEMBER_TYPE_RESPONSIBLE, 'GROUP_ID' => $groupId]
				);
				$this->modifyEfficiencyForResponsible($newResponsibleId, $newTaskData, $groupId, false);
				$processedMembers[$newResponsibleId] = $newResponsibleId;
			}
			else
			{
				$this->modifyEfficiencyForResponsible($newResponsibleId, $newTaskData, $groupId, $isViolation);
				$processedMembers[$newResponsibleId] = $newResponsibleId;
			}

			$responsibleModified = true;
		}

		// ACCOMPLICES CHANGED
		if ($accomplicesChanged)
		{
			foreach ($accomplicesOut as $userId)
			{
				if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
				{
					Effective::repair($taskId, $userId, MemberTable::MEMBER_TYPE_ACCOMPLICE);
					$this->modifyEfficiencyForAccomplice($userId, $oldTaskData, $oldGroupId, false);
					$processedMembers[$userId] = $userId;
				}
			}
			foreach ($accomplicesIn as $userId)
			{
				if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
				{
					$this->modifyEfficiencyForAccomplice($userId, $newTaskData, $groupId, $isViolation);
					$processedMembers[$userId] = $userId;
				}
			}
		}

		// DEADLINE CHANGED
		if ($deadlineChanged && !$isViolation)
		{
			Effective::repair($taskId);

			if (!$responsibleModified)
			{
				$this->modifyEfficiencyForResponsible($oldResponsibleId, $newTaskData, $groupId, false);
				$processedMembers[$oldResponsibleId] = $oldResponsibleId;
				$responsibleModified = true;
			}
			if (!$accomplicesModified)
			{
				$accomplices = ($accomplicesChanged ? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);
				foreach ($accomplices as $userId)
				{
					if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
					{
						$this->modifyEfficiencyForAccomplice($userId, $newTaskData, $groupId, false);
						$processedMembers[$userId] = $userId;
					}
				}
				$accomplicesModified = true;
			}
		}

		// GROUP CHANGED
		if ($groupChanged)
		{
			if ($activeViolations = Effective::checkActiveViolations($taskId))
			{
				foreach ($activeViolations as $violation)
				{
					EffectiveTable::update($violation['ID'], ['GROUP_ID' => $newGroupId]);
				}

				if (!$responsibleModified)
				{
					$this->modifyEfficiencyForResponsible($oldResponsibleId, $newTaskData, $newGroupId, false);
					$processedMembers[$oldResponsibleId] = $oldResponsibleId;
				}
				if (!$accomplicesModified)
				{
					$accomplices = ($accomplicesChanged ? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);
					foreach ($accomplices as $userId)
					{
						if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
						{
							$this->modifyEfficiencyForAccomplice($userId, $newTaskData, $newGroupId, false);
							$processedMembers[$userId] = $userId;
						}
					}
				}
			}
			else
			{
				if (!$responsibleModified)
				{
					$this->modifyEfficiencyForResponsible($oldResponsibleId, $newTaskData, $newGroupId, $isViolation);
					$processedMembers[$oldResponsibleId] = $oldResponsibleId;
				}
				if (!$accomplicesModified)
				{
					$accomplices = ($accomplicesChanged ? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);
					foreach ($accomplices as $userId)
					{
						if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
						{
							$this->modifyEfficiencyForAccomplice($userId, $newTaskData, $newGroupId, $isViolation);
							$processedMembers[$userId] = $userId;
						}
					}
				}
			}
		}

		return $processedMembers;
	}

	/**
	 * @param int $userId
	 * @param array $taskData
	 * @param int $groupId
	 * @param bool|null $isViolation
	 * @param bool $recountEfficiency
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function modifyEfficiencyForResponsible(
		int $userId,
		array $taskData,
		int $groupId,
		bool $isViolation = null
	): void
	{
		$userType = MemberTable::MEMBER_TYPE_RESPONSIBLE;
		Effective::modify($userId, $userType, $taskData, $groupId, $isViolation, false);
	}

	/**
	 * @param int $userId
	 * @param array $taskData
	 * @param int $groupId
	 * @param bool|null $isViolation
	 * @param bool $recountEfficiency
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function modifyEfficiencyForAccomplice(
		int $userId,
		array $taskData,
		int $groupId,
		bool $isViolation = null
	): void
	{
		$userType = MemberTable::MEMBER_TYPE_ACCOMPLICE;
		Effective::modify($userId, $userType, $taskData, $groupId, $isViolation, false);
	}
}