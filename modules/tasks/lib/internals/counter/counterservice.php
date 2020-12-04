<?php
namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Util\Type\DateTime;

/**
 * Class CounterService
 *
 * @package Bitrix\Tasks\Internals\Counter
 */
class CounterService
{
	private static $instance;
	private static $jobOn 	= false;

	private $originData 	= [];
	private $updatedData 	= [];

	private $registry 		= [];

	/**
	 * CounterService constructor.
	 */
	private function __construct()
	{

	}

	/**
	 * @return CounterService
	 */
	public static function getInstance(): CounterService
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param string $type
	 * @param array $data
	 */
	public static function addEvent(string $type, array $data): void
	{
		self::getInstance()->registerEvent(new CounterEvent($type, $data));

		if (!self::$jobOn)
		{
			$application = Application::getInstance();
			$application && $application->addBackgroundJob(
				['\Bitrix\Tasks\Internals\Counter\CounterService', 'updateCounters'],
				[],
				Application::JOB_PRIORITY_LOW - 2
			);

			self::$jobOn = true;
		}
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function updateCounters(): void
	{
		self::getInstance()->handleEvents();
	}

	/**
	 * @param int $userId
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function recountForUser(int $userId): void
	{
		$counter = Counter::getInstance($userId);
		$counter->recount(CounterDictionary::COUNTER_EXPIRED);
		PushSender::send([$userId]);
	}

	/**
	 * @param int $taskId
	 */
	public function collectData(int $taskId): void
	{
		if ($taskId && !$this->originData[$taskId])
		{
			$this->originData[$taskId] = (new TaskResource($taskId))->fill();
		}
	}

	/**
	 * @param CounterEvent $event
	 */
	private function registerEvent(CounterEvent $event): void
	{
		$this->registry[] = $event;
	}

	/**
	 *
	 */
	private function handleEvents(): void
	{
		$this->collectUpdatedData();

		$deletedTasks = $this->getEventsTasks(CounterDictionary::EVENT_AFTER_TASK_DELETE);

		$toUpdate = [
			CounterDictionary::COUNTER_NEW_COMMENTS => [],
			CounterDictionary::COUNTER_EXPIRED => []
		];
		$efficiencyUpdated = [];
		$readAll = 0;
		$toDelete = [];

		foreach ($this->registry as $event)
		{
			$taskId = $event->getTaskId();
			$eventType = $event->getType();

			if ($eventType === CounterDictionary::EVENT_AFTER_TASK_DELETE)
			{
				$efficiencyUpdated[$taskId] = $taskId;
				if (array_key_exists($taskId, $this->originData))
				{
					$toDelete[$taskId] = $this->originData[$taskId];
				}
			}

			/**
			 * need to update expires and comments counter
			 */
			if (in_array($eventType, [
				CounterDictionary::EVENT_AFTER_TASK_RESTORE,
				CounterDictionary::EVENT_AFTER_TASK_ADD,
				CounterDictionary::EVENT_AFTER_TASK_MUTE,
				CounterDictionary::EVENT_AFTER_TASK_UPDATE,
				CounterDictionary::EVENT_TASK_EXPIRED
			]))
			{
				$toUpdate[CounterDictionary::COUNTER_EXPIRED][] = $taskId;
				$toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS][] = $taskId;
				$efficiencyUpdated[$taskId] = $taskId;
			}

			/**
			 * need to update comments counter
			 */
			if (in_array($eventType, [
				CounterDictionary::EVENT_AFTER_TASK_VIEW,
				CounterDictionary::EVENT_AFTER_COMMENT_ADD,
				CounterDictionary::EVENT_AFTER_COMMENT_DELETE,
				CounterDictionary::EVENT_AFTER_TASK_MUTE
			]))
			{
				$toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS][] = $taskId;
			}

			if ($eventType === CounterDictionary::EVENT_AFTER_COMMENTS_READ_ALL)
			{
				$readAll = $event->getUserId();
			}

			/**
			 * need to remove agent
			 */
			if ($eventType === CounterDictionary::EVENT_AFTER_TASK_DELETE)
			{
				Agent::remove($taskId);
			}

			/**
			 * need to add agent
			 */
			if (
				in_array($eventType, [
					CounterDictionary::EVENT_AFTER_TASK_RESTORE,
					CounterDictionary::EVENT_AFTER_TASK_ADD
				])
				&& array_key_exists($taskId, $this->updatedData)
				&& !in_array($taskId, $deletedTasks)
			)
			{
				/** @var TaskResource $task */
				$task = $this->updatedData[$taskId];
				if ($task->getDeadline() && !$task->isExpired())
				{
					Agent::add($taskId, $task->getDeadline());
				}
			}

			/**
			 * need to update agent
			 */
			if (
				$eventType === CounterDictionary::EVENT_AFTER_TASK_UPDATE
				&& array_key_exists($taskId, $this->originData)
				&& array_key_exists($taskId, $this->updatedData)
				&& !in_array($taskId, $deletedTasks)
			)
			{
				/** @var TaskResource $oldTask */
				$oldTask = $this->originData[$taskId];
				/** @var TaskResource $newTask */
				$newTask = $this->updatedData[$taskId];
				$this->updateAgents($oldTask, $newTask);
			}
		}

		$deletedMembers = $this->handleDeleted($toDelete);

		$taskIds = array_unique(array_merge($toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS], $toUpdate[CounterDictionary::COUNTER_EXPIRED]));
		$taskIds = array_diff($taskIds, array_keys($toDelete));
		$members = $this->getTasksMembers($taskIds);

		foreach ($members as $userId => $taskIds)
		{
			$counter = Counter::getInstance($userId);
			if (
				$userId !== $readAll
				&& array_intersect($taskIds, $toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS])
			)
			{
				$counter->recount(CounterDictionary::COUNTER_NEW_COMMENTS, $taskIds);
			}
			if (array_intersect($taskIds, $toUpdate[CounterDictionary::COUNTER_EXPIRED]))
			{
				$counter->recount(CounterDictionary::COUNTER_EXPIRED, $taskIds);
			}
		}

		$members = array_keys($members);
		if ($readAll)
		{
			$counter = Counter::getInstance($readAll);
			$counter->readAll();

			$members[] = $readAll;
		}

		PushSender::send(array_unique(array_merge($members, $deletedMembers)));

		if (!empty($efficiencyUpdated))
		{
			$this->updateEfficiency($efficiencyUpdated);
		}
	}

	/**
	 * @param array $toDelete
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function handleDeleted(array $toDelete): array
	{
		if (empty($toDelete))
		{
			return [];
		}

		$list = [];
		foreach ($toDelete as $taskId => $task)
		{
			/* @var TaskResource $task */
			foreach ($task->getMemberIds() as $memberId)
			{
				$list[$memberId][$taskId] = $taskId;
			}
		}

		foreach ($list as $memberId => $tasks)
		{
			$counter = Counter::getInstance((int) $memberId);
			$counter->deleteTasks($tasks);
		}

		return array_keys($list);
	}

	/**
	 * @param array $taskIds
	 * @return array
	 * @throws Main\Db\SqlQueryException
	 */
	private function getTasksMembers(array $taskIds): array
	{
		if (
			empty($taskIds)
			|| (count($taskIds) === 1 && $taskIds[0] == 0)
		)
		{
			return [];
		}


		$members = [];
		foreach ($taskIds as $taskId)
		{
			$taskMembers = [];
			if (isset($this->originData[$taskId]))
			{
				$taskMembers = array_merge($taskMembers, $this->originData[$taskId]->getMemberIds());
			}
			if (isset($this->updatedData[$taskId]))
			{
				$taskMembers = array_merge($taskMembers, $this->updatedData[$taskId]->getMemberIds());
			}

			foreach ($taskMembers as $userId)
			{
				$members[(int) $userId][$taskId] = (int) $taskId;
			}
		}

		if (!isset($this->updatedData[$taskId]))
		{
			$sql = "
				SELECT 
					TASK_ID,
					USER_ID
				FROM b_tasks_member
				WHERE TASK_ID IN (". implode(',', $taskIds) .")
			";
			$res = Application::getConnection()->query($sql);

			while ($row = $res->fetch())
			{
				$members[(int) $row['USER_ID']][$row['TASK_ID']] = (int) $row['TASK_ID'];
			}
		}


		return $members;
	}


	/**
	 * @param array $ids
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function updateEfficiency(array $ids): void
	{
		$deletedTasks 	= $this->getEventsTasks(CounterDictionary::EVENT_AFTER_TASK_DELETE);
		$addedTasks 	= $this->getEventsTasks(CounterDictionary::EVENT_AFTER_TASK_ADD);
		$restoredTasks 	= $this->getEventsTasks(CounterDictionary::EVENT_AFTER_TASK_RESTORE);
		$expiredTasks 	= $this->getEventsTasks(CounterDictionary::EVENT_TASK_EXPIRED);

		foreach ($ids as $taskId)
		{
			if (in_array($taskId, $deletedTasks, true))
			{
				$this->updateEfficiencyForDeletedAndAdded($taskId, true);
			}
			elseif (in_array($taskId, $addedTasks, true))
			{
				$this->updateEfficiencyForDeletedAndAdded($taskId);
			}
			elseif (in_array($taskId, $restoredTasks, true))
			{
				$this->updateEfficiencyForRestored($taskId);
			}
			elseif (in_array($taskId, $expiredTasks, true))
			{
				$this->updateEfficiencyForExpired($taskId);
			}
			else
			{
				$this->updateEfficiencyForUpdated($taskId);
			}
		}
	}

	/**
	 * @param string $eventType
	 * @return array
	 */
	private function getEventsTasks(string $eventType): array
	{
		$res = [];
		foreach ($this->registry as $event)
		{
			if ($event->getType() === $eventType)
			{
				$res[] = $event->getTaskId();
			}
		}
		return $res;
	}

	/**
	 * @param TaskResource $oldData
	 * @param TaskResource $newData
	 */
	private function updateAgents(TaskResource $oldData, TaskResource $newData): void
	{
		if (
			$oldData->getDeadline()
			&& $newData->getDeadline()
			&& $oldData->getDeadline()->isEqualTo($newData->getDeadline())
		)
		{
			return;
		}

		$taskId = $oldData->getId();

		if ($newData->getDeadline() && (!$oldData->isExpired() || !$newData->isExpired()))
		{
			Agent::add($taskId, $newData->getDeadline());
		}
		else
		{
			Agent::remove($taskId);
		}
	}

	/**
	 * @param int $taskId
	 * @param bool $isDeleted
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function updateEfficiencyForDeletedAndAdded(int $taskId, bool $isDeleted = false): bool
	{
		/** @var TaskResource $task */
		$task = ($this->updatedData[$taskId] ?? $this->originData[$taskId]);
		if (!$task)
		{
			return false;
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
		foreach ($processedMembers as $userId)
		{
			Effective::recountEfficiencyUserCounter($userId);
		}

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function updateEfficiencyForRestored(int $taskId): bool
	{
		/** @var TaskResource $task */
		$task = $this->updatedData[$taskId];
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
		foreach ($processedMembers as $userId)
		{
			Effective::recountEfficiencyUserCounter($userId);
		}

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function updateEfficiencyForExpired(int $taskId): bool
	{
		/** @var TaskResource $task */
		$task = $this->updatedData[$taskId];
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
		foreach ($processedMembers as $userId)
		{
			Effective::recountEfficiencyUserCounter($userId);
		}

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	private function updateEfficiencyForUpdated(int $taskId): bool
	{
		/** @var TaskResource $oldTask */
		$oldTask = $this->originData[$taskId];
		/** @var TaskResource $newTask */
		$newTask = $this->updatedData[$taskId];

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

		// TASK DEFERRED OR COMPLETED
		if ($statusChanged && in_array($newStatus, $statesCompleted, true))
		{
			Effective::repair($taskId);
			$this->modifyEfficiencyForResponsible($oldResponsibleId, $oldTaskData, $oldGroupId, false);

			foreach ($oldAccomplices as $userId)
			{
				if ($userId !== $oldResponsibleId)
				{
					$this->modifyEfficiencyForAccomplice($userId, $oldTaskData, $oldGroupId, false);
				}
			}

			if ($responsibleChanged)
			{
				$this->modifyEfficiencyForResponsible($newResponsibleId, $newTaskData, $groupId, false);
			}
			if ($accomplicesChanged)
			{
				foreach ($accomplicesIn as $userId)
				{
					if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
					{
						$this->modifyEfficiencyForAccomplice($userId, $newTaskData, $groupId, false);
					}
				}
			}

			return true;
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
				$responsibleModified = true;
			}
			if (!$accomplicesChanged)
			{
				foreach ($oldAccomplices as $userId)
				{
					if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
					{
						$this->modifyEfficiencyForAccomplice($userId, $oldTaskData, $groupId, $isViolation);
					}
				}
				$accomplicesModified = true;
			}

			$canProceed = true;
		}

		if (!$canProceed && in_array($oldStatus, $statesCompleted, true))
		{
			return true;
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

			if ($activeViolations = Effective::checkActiveViolations($taskId, $newResponsibleId))
			{
				EffectiveTable::update(
					$activeViolations[0]['ID'],
					['USER_TYPE' => MemberTable::MEMBER_TYPE_RESPONSIBLE, 'GROUP_ID' => $groupId]
				);
				$this->modifyEfficiencyForResponsible($newResponsibleId, $newTaskData, $groupId, false);
			}
			else
			{
				$this->modifyEfficiencyForResponsible($newResponsibleId, $newTaskData, $groupId, $isViolation);
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
				}
			}
			foreach ($accomplicesIn as $userId)
			{
				if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
				{
					$this->modifyEfficiencyForAccomplice($userId, $newTaskData, $groupId, $isViolation);
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
				}
				if (!$accomplicesModified)
				{
					$accomplices = ($accomplicesChanged ? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);
					foreach ($accomplices as $userId)
					{
						if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
						{
							$this->modifyEfficiencyForAccomplice($userId, $newTaskData, $newGroupId, false);
						}
					}
				}
			}
			else
			{
				if (!$responsibleModified)
				{
					$this->modifyEfficiencyForResponsible($oldResponsibleId, $newTaskData, $newGroupId, $isViolation);
				}
				if (!$accomplicesModified)
				{
					$accomplices = ($accomplicesChanged ? array_diff($newAccomplices, $accomplicesIn) : $allAccomplices);
					foreach ($accomplices as $userId)
					{
						if ($userId !== $oldResponsibleId && $userId !== $newResponsibleId)
						{
							$this->modifyEfficiencyForAccomplice($userId, $newTaskData, $newGroupId, $isViolation);
						}
					}
				}
			}
		}

		return true;
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
		bool $isViolation = null,
		bool $recountEfficiency = true
	): void
	{
		$userType = MemberTable::MEMBER_TYPE_RESPONSIBLE;
		Effective::modify($userId, $userType, $taskData, $groupId, $isViolation, $recountEfficiency);
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
		bool $isViolation = null,
		bool $recountEfficiency = true
	): void
	{
		$userType = MemberTable::MEMBER_TYPE_ACCOMPLICE;
		Effective::modify($userId, $userType, $taskData, $groupId, $isViolation, $recountEfficiency);
	}

	/**
	 * @param int $taskId
	 */
	private function collectUpdatedData(): void
	{
		foreach ($this->registry as $event)
		{
			$taskId = $event->getTaskId();
			$eventType = $event->getType();
			if (in_array($eventType, [
				CounterDictionary::EVENT_AFTER_TASK_DELETE,
				CounterDictionary::EVENT_AFTER_COMMENTS_READ_ALL
			]))
			{
				continue;
			}

			if (
				array_key_exists($taskId, $this->updatedData)
				&& $this->updatedData[$taskId]
			)
			{
				continue;
			}
			$this->updatedData[$taskId] = (new TaskResource($taskId))->fill();
		}
	}
}