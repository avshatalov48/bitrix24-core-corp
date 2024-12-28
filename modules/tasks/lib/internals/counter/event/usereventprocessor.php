<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Event;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Tasks\Integration\SocialNetwork\Collab\Counter\CollabListener;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\Agent;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterController;
use Bitrix\Tasks\Internals\Counter\Push\PushSender;
use Bitrix\Tasks\Internals\Marketing\Event\QrMobileEvent;
use Bitrix\Tasks\Internals\Marketing\EventManager;

class UserEventProcessor
{
	/**
	 *
	 */
	public function process(): void
	{
		$deletedTasks = EventCollection::getInstance()->getTasksByEventType(EventDictionary::EVENT_AFTER_TASK_DELETE);

		$toUpdate = [
			CounterDictionary::COUNTER_NEW_COMMENTS => [],
			CounterDictionary::COUNTER_EXPIRED => []
		];
		$efficiencyUpdated = [];
		$readAll = null;
		$toDelete = [];

		$originData = $this->getResourceCollection()->getOrigin();
		$updatedData = $this->getResourceCollection()->getModified();

		$events = (EventCollection::getInstance())->list();
		foreach ($events as $event)
		{
			/* @var $event Event */
			$userId = $event->getUserId();
			$taskId = $event->getTaskId();
			$eventType = $event->getType();

			if ($eventType === EventDictionary::EVENT_AFTER_COMMENTS_READ_ALL)
			{
				$readAll = $event;
				continue;
			}

			if ($eventType === EventDictionary::EVENT_AFTER_TASK_DELETE)
			{
				$efficiencyUpdated[$taskId] = $taskId;
				if (array_key_exists($taskId, $originData))
				{
					$toDelete[$taskId] = $originData[$taskId];
				}
			}

			/**
			 * need to update expires and comments counter
			 */
			if (in_array($eventType, [
				EventDictionary::EVENT_AFTER_TASK_RESTORE,
				EventDictionary::EVENT_AFTER_TASK_ADD,
				EventDictionary::EVENT_AFTER_TASK_MUTE,
				EventDictionary::EVENT_AFTER_TASK_UPDATE,
				EventDictionary::EVENT_TASK_EXPIRED
			]))
			{
				$toUpdate[CounterDictionary::COUNTER_EXPIRED][] = $taskId;
				$toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS][] = $taskId;
				$efficiencyUpdated[$taskId] = $taskId;
			}

			/**
			 * need to update comments counter
			 */
			if ($eventType === EventDictionary::EVENT_AFTER_TASK_VIEW)
			{
				$counts = Counter::getInstance($userId)->getCommentsCount([$taskId]);
				if (isset($counts[$taskId]) && $counts[$taskId] > 0)
				{
					$toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS][] = $taskId;
				}
			}

			if (in_array($eventType, [
				EventDictionary::EVENT_AFTER_COMMENT_ADD,
				EventDictionary::EVENT_AFTER_COMMENT_DELETE,
				EventDictionary::EVENT_AFTER_TASK_MUTE
			]))
			{
				$toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS][] = $taskId;
			}

			/**
			 * need to remove agent
			 */
			if ($eventType === EventDictionary::EVENT_AFTER_TASK_DELETE)
			{
				Agent::remove($taskId);
			}

			/**
			 * need to add agent
			 */
			if (
				in_array($eventType, [
					EventDictionary::EVENT_AFTER_TASK_RESTORE,
					EventDictionary::EVENT_AFTER_TASK_ADD
				])
				&& array_key_exists($taskId, $updatedData)
				&& !in_array($taskId, $deletedTasks)
			)
			{
				/** @var EventResource $task */
				$task = $updatedData[$taskId];
				if ($task->getDeadline() && !$task->isExpired())
				{
					Agent::add($taskId, $task->getDeadline());
				}
			}

			/**
			 * need to update agent
			 */
			if (
				$eventType === EventDictionary::EVENT_AFTER_TASK_UPDATE
				&& array_key_exists($taskId, $originData)
				&& array_key_exists($taskId, $updatedData)
				&& !in_array($taskId, $deletedTasks)
			)
			{
				/** @var EventResource $oldTask */
				$oldTask = $originData[$taskId];
				/** @var EventResource $newTask */
				$newTask = $updatedData[$taskId];
				$this->updateAgents($oldTask, $newTask);
			}
		}

		$readAllUser = 0;
		if ($readAll)
		{
			$readAllUser = $readAll->getUserId();
			(new CounterController($readAllUser))->readAll($readAll->getGroupId(), $readAll->getData()['ROLE']);
		}

		$deletedMembers = $this->handleDeleted($toDelete);
		$members = $this->handleUpdated($toUpdate, $toDelete, $readAllUser);

		$users = array_unique(array_merge($members, $deletedMembers));
		(new PushSender())->sendUserCounters($users);

		if (!empty($efficiencyUpdated))
		{
			(new Counter\Processor\EfficiencyProcessor())->recount($efficiencyUpdated);
		}

		(new CollabListener())->notify($this->getResourceCollection(), EventCollection::getInstance());
	}

	/**
	 * @return EventResourceCollection
	 */
	private function getResourceCollection(): EventResourceCollection
	{
		return EventResourceCollection::getInstance();
	}

	/**
	 * @param EventResource $oldData
	 * @param EventResource $newData
	 */
	private function updateAgents(EventResource $oldData, EventResource $newData): void
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

		$memberIds = [];
		foreach ($toDelete as $taskId => $task)
		{
			/* @var EventResource $task */
			foreach ($task->getMemberIds() as $memberId)
			{
				$memberIds[$memberId] = $memberId;
			}
		}
		$memberIds = array_keys($memberIds);

		(new CounterController())->deleteTasks(array_keys($toDelete), $memberIds);

		return $memberIds;
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
		$taskIds = array_values(array_diff($taskIds, array_keys($toDelete)));
		$members = $this->getTasksMembers($taskIds);

		foreach ($members as $userId => $taskIds)
		{
			$counterController = new CounterController($userId);
			if (
				$userId !== $readAll
				&& array_intersect($taskIds, $toUpdate[CounterDictionary::COUNTER_NEW_COMMENTS])
			)
			{
				$counterController->recount(CounterDictionary::COUNTER_NEW_COMMENTS, $taskIds);
			}
			if (array_intersect($taskIds, $toUpdate[CounterDictionary::COUNTER_EXPIRED]))
			{
				$counterController->recount(CounterDictionary::COUNTER_EXPIRED, $taskIds);
			}
		}

		$members = array_keys($members);

		if ($readAll)
		{
			$members[] = $readAll;
		}

		return $members;
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
			|| (count($taskIds) === 1 && (int) $taskIds[0] === 0)
		)
		{
			return [];
		}

		$originData = $this->getResourceCollection()->getOrigin();
		$updatedData = $this->getResourceCollection()->getModified();


		$members = [];
		foreach ($taskIds as $taskId)
		{
			$taskMembers = [];
			if (isset($originData[$taskId]))
			{
				$taskMembers = array_merge($taskMembers, $originData[$taskId]->getMemberIds());
			}
			if (isset($updatedData[$taskId]))
			{
				$taskMembers = array_merge($taskMembers, $updatedData[$taskId]->getMemberIds());
			}

			foreach ($taskMembers as $userId)
			{
				$members[(int) $userId][$taskId] = (int) $taskId;
			}
		}

		if (!isset($updatedData[$taskId]))
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
}