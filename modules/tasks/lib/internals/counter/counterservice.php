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
	private const LOCK_KEY = 'tasks.countlock';

	private static $instance;
	private static $jobOn 	= false;

	private $originData 	= [];
	private $updatedData 	= [];

	private $registry 		= [];

	private static $hitId;

	/**
	 * CounterService constructor.
	 */
	private function __construct()
	{
		self::$hitId = $this->generateHid();
		$this->enableJob();
		$this->handleLostEvents();
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
		self::getInstance()->registerEvent($type, $data);
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
		$service = self::getInstance();

		if (empty($service->registry))
		{
			Application::getConnection()->unlock(self::LOCK_KEY);
			return;
		}

		$service->collectUpdatedData();
		$service->handleEvents();
		$service->done();
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
		(new CounterProcessor($userId))->recount(CounterDictionary::COUNTER_EXPIRED);
		PushSender::send([$userId]);
	}

	/**
	 * @param int $taskId
	 */
	public function collectData(int $taskId, array $resourceData = null): void
	{
		if (!$taskId || array_key_exists($taskId, $this->originData))
		{
			return;
		}

		if (!$resourceData)
		{
			$this->originData[$taskId] = (new TaskResource($taskId))->fill();
			return;
		}

		$resource = TaskResource::invokeFromArray($resourceData);
		if (!$resource)
		{
			return;
		}

		$this->originData[$taskId] = $resource;
	}

	/**
	 *
	 */
	private function done(): void
	{
		$ids = [];
		foreach ($this->registry as $event)
		{
			$id = $event->getId();
			if ($id)
			{
				$ids[] = $id;
			}
		}

		if (empty($ids))
		{
			return;
		}

		Counter\Event\EventTable::markProcessed([
			'@ID' => $ids
		]);

		Application::getConnection()->unlock(self::LOCK_KEY);
	}

	private function handleLostEvents(): void
	{
		if (!Application::getConnection()->lock(self::LOCK_KEY))
		{
			return;
		}

		$events = Counter\Event\EventTable::getLostEvents();

		if (empty($events))
		{
			return;
		}

		foreach ($events as $row)
		{
			$event = new CounterEvent(
				$row['HID'],
				$row['TYPE']
			);
			$event
				->setId($row['ID'])
				->setData(Main\Web\Json::decode($row['DATA']));
			$this->registry[] = $event;

			$taskData = !empty($taskData) ? Main\Web\Json::decode($row['TASK_DATA']) : null;
			if ($taskData && array_key_exists('ID', $taskData))
			{
				$this->collectData((int)$taskData['ID'], $taskData);
			}
		}
	}

	/**
	 *
	 */
	private function enableJob(): void
	{
		if (self::$jobOn)
		{
			return;
		}

		$application = Application::getInstance();
		$application && $application->addBackgroundJob(
			['\Bitrix\Tasks\Internals\Counter\CounterService', 'updateCounters'],
			[],
			Application::JOB_PRIORITY_LOW - 2
		);

		self::$jobOn = true;
	}

	/**
	 * @param string $type
	 * @param array $data
	 */
	private function registerEvent(string $type, array $data): void
	{
		$event = new CounterEvent(self::$hitId, $type);
		$event->setData($data);

		$eventId = $this->saveToDb($event);
		$event->setId($eventId);

		$this->registry[] = $event;
	}

	/**
	 * @param string $type
	 * @param array $data
	 * @return int
	 */
	private function saveToDb(CounterEvent $event): int
	{
		try
		{
			$taskId = $event->getTaskId();
			$taskData = null;
			if ($taskId && array_key_exists($taskId, $this->originData))
			{
				$taskData = $this->originData[$taskId];
			}

			$res = Counter\Event\EventTable::add([
				'HID' => self::$hitId,
				'TYPE' => $event->getType(),
				'DATA' => Main\Web\Json::encode($event->getData()),
				'TASK_DATA' => $taskData ? Main\Web\Json::encode($taskData->toArray()) : null,
			]);
		}
		catch (\Exception $e)
		{
			return 0;
		}

		return (int)$res->getId();
	}

	/**
	 *
	 */
	private function handleEvents(): void
	{
		$deletedTasks = $this->getEventsTasks(CounterDictionary::EVENT_AFTER_TASK_DELETE);

		$toUpdate = [
			CounterDictionary::COUNTER_NEW_COMMENTS => [],
			CounterDictionary::COUNTER_EXPIRED => []
		];
		$efficiencyUpdated = [];
		$readAll = null;
		$toDelete = [];

		foreach ($this->registry as $event)
		{
			/* @var $event CounterEvent */
			$userId = $event->getUserId();
			$taskId = $event->getTaskId();
			$eventType = $event->getType();


			if ($eventType === CounterDictionary::EVENT_AFTER_COMMENTS_READ_ALL)
			{
				$readAll = $event;
				continue;
			}

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
			if ($eventType === CounterDictionary::EVENT_AFTER_TASK_VIEW)
			{
				$counts = Counter::getInstance($userId)->getCommentsCount([$taskId]);
				if (isset($counts[$taskId]) && $counts[$taskId] > 0)
				{
					$toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS][] = $taskId;
				}
			}

			if (in_array($eventType, [
				CounterDictionary::EVENT_AFTER_COMMENT_ADD,
				CounterDictionary::EVENT_AFTER_COMMENT_DELETE,
				CounterDictionary::EVENT_AFTER_TASK_MUTE
			]))
			{
				$toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS][] = $taskId;
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

		$readAllUser = 0;
		if ($readAll)
		{
			$readAllUser = $readAll->getUserId();
		}

		$deletedMembers = $this->handleDeleted($toDelete);
		$members = $this->handleUpdated($toUpdate, $toDelete, $readAllUser);

		if ($readAll)
		{
			(new CounterProcessor($readAllUser))->readAll($readAll->getData()['GROUP_ID'], $readAll->getData()['ROLE']);

			$members[] = $readAllUser;
		}

		PushSender::send(array_unique(array_merge($members, $deletedMembers)));

		if (!empty($efficiencyUpdated))
		{
			$this->updateEfficiency($efficiencyUpdated);
		}
	}

	/**
	 * @param array $toUpdate
	 * @param array $toDelete
	 * @param int $readAll
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function handleUpdated(array $toUpdate, array $toDelete, $readAll): array
	{
		$taskIds = array_unique(array_merge($toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS], $toUpdate[CounterDictionary::COUNTER_EXPIRED]));
		$taskIds = array_diff($taskIds, array_keys($toDelete));
		$members = $this->getTasksMembers($taskIds);

		foreach ($members as $userId => $taskIds)
		{
			$counterProcessor = new CounterProcessor($userId);
			if (
				$userId !== $readAll
				&& array_intersect($taskIds, $toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS])
			)
			{
				$counterProcessor->recount(CounterDictionary::COUNTER_NEW_COMMENTS, $taskIds);
			}
			if (array_intersect($taskIds, $toUpdate[CounterDictionary::COUNTER_EXPIRED]))
			{
				$counterProcessor->recount(CounterDictionary::COUNTER_EXPIRED, $taskIds);
			}
		}

		return array_keys($members);
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
				$list[$memberId][$taskId] = $task->getGroupId();
			}
		}

		foreach ($list as $memberId => $tasks)
		{
			(new CounterProcessor((int) $memberId))->deleteTasks($tasks);
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
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function updateEfficiencyForDeletedAndAdded(int $taskId, bool $isDeleted = false): array
	{
		/** @var TaskResource $task */
		$task = ($this->updatedData[$taskId] ?? $this->originData[$taskId]);
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
			|| !array_key_exists($taskId, $this->updatedData)
		)
		{
			return [];
		}

		/** @var TaskResource $oldTask */
		$oldTask = $this->originData[$taskId];
		/** @var TaskResource $newTask */
		$newTask = $this->updatedData[$taskId];

		if (!$oldTask || !$newTask)
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

	/**
	 * @return string
	 */
	private function generateHid(): string
	{
		return sha1(microtime(true) . mt_rand(10000, 99999));
	}
}