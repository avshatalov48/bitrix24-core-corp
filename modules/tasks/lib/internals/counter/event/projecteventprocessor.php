<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Event;


use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\FeaturePermTable;
use Bitrix\Socialnetwork\FeatureTable;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\CounterController;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\Push\GroupSender;
use Bitrix\Tasks\Internals\Counter\Push\PushSender;

class ProjectEventProcessor
{

	/**
	 *
	 */
	public function process(): void
	{
		if (!Counter::isSonetEnable())
		{
			return;
		}

		$originData = $this->getResourceCollection()->getOrigin();
		$updatedData = $this->getResourceCollection()->getModified();

		$events = (EventCollection::getInstance())->list();

		$recountExpired = [];
		$recountComments = [];
		$projectPermUpdated = [];
		$pushList = [];
		$personalPush = [];

		foreach ($events as $event)
		{
			if (in_array($event, [
				EventDictionary::EVENT_TASK_EXPIRED_SOON
			]))
			{
				continue;
			}

			/* @var $event Event */
			$eventType = $event->getType();
			$taskId = $event->getTaskId();
			$groupId = $event->getGroupId();

			switch ($eventType)
			{
				case EventDictionary::EVENT_AFTER_PROJECT_READ_ALL:
					(new CounterController($event->getUserId()))->readProject($groupId);
					$pushList[] = [
						'EVENT' => $eventType,
						'USER_ID' => $event->getUserId(),
					];
					$personalPush[] = $event->getUserId();
					break;

				case EventDictionary::EVENT_AFTER_SCRUM_READ_ALL:
					(new CounterController($event->getUserId()))->readScrum($groupId);
					$pushList[] = [
						'EVENT' => $eventType,
						'USER_ID' => $event->getUserId(),
					];
					$personalPush[] = $event->getUserId();
					break;

				case EventDictionary::EVENT_PROJECT_DELETE:
					CounterController::reset(0, [CounterDictionary::COUNTER_GROUP_EXPIRED, CounterDictionary::COUNTER_GROUP_COMMENTS], [], [$groupId]);
					break;

				case EventDictionary::EVENT_PROJECT_USER_DELETE:
					CounterController::reset($event->getUserId(), [CounterDictionary::COUNTER_GROUP_EXPIRED, CounterDictionary::COUNTER_GROUP_COMMENTS], [], [$groupId]);
					break;

				case EventDictionary::EVENT_AFTER_TASK_VIEW:
					(new CounterController($event->getUserId()))->recount(CounterDictionary::COUNTER_GROUP_COMMENTS, [$taskId]);
					break;

				case EventDictionary::EVENT_PROJECT_USER_ADD:
					(new CounterController($event->getUserId()))->recount(CounterDictionary::COUNTER_GROUP_EXPIRED, [], [$groupId]);
					(new CounterController($event->getUserId()))->recount(CounterDictionary::COUNTER_GROUP_COMMENTS, [], [$groupId]);
					break;

				case EventDictionary::EVENT_PROJECT_USER_UPDATE:
					(new CounterController($event->getUserId()))->recount(CounterDictionary::COUNTER_GROUP_EXPIRED, [], [$groupId]);
					(new CounterController($event->getUserId()))->recount(CounterDictionary::COUNTER_GROUP_COMMENTS, [], [$groupId]);
					break;

				case EventDictionary::EVENT_PROJECT_PERM_UPDATE:
					$projectPermUpdated[] = $event->getData()['FEATURE_PERM'];
					break;

				case EventDictionary::EVENT_AFTER_TASK_ADD:
				case EventDictionary::EVENT_AFTER_TASK_RESTORE:
				case EventDictionary::EVENT_AFTER_TASK_UPDATE:
				case EventDictionary::EVENT_AFTER_TASK_DELETE:
					$recountExpired[] = $taskId;
					$recountComments[] = $taskId;
					break;

				case EventDictionary::EVENT_AFTER_COMMENT_ADD:
				case EventDictionary::EVENT_AFTER_COMMENT_DELETE:
					$recountComments[] = $taskId;
					break;

				case EventDictionary::EVENT_TASK_EXPIRED:
					$recountExpired[] = $taskId;
					break;

			}

			$groupEventsForUsers = [
				EventDictionary::EVENT_PROJECT_USER_ADD,
				EventDictionary::EVENT_PROJECT_USER_UPDATE,
				EventDictionary::EVENT_PROJECT_USER_DELETE,
				EventDictionary::EVENT_AFTER_COMMENTS_READ_ALL,
				EventDictionary::EVENT_AFTER_PROJECT_READ_ALL,
				EventDictionary::EVENT_AFTER_SCRUM_READ_ALL,
			];
			$taskEventsForUsers = [
				EventDictionary::EVENT_AFTER_TASK_MUTE,
				EventDictionary::EVENT_AFTER_TASK_VIEW,
			];
			$taskUpdatedEvents = [
				EventDictionary::EVENT_AFTER_TASK_ADD,
				EventDictionary::EVENT_AFTER_TASK_RESTORE,
				EventDictionary::EVENT_AFTER_TASK_UPDATE,
				EventDictionary::EVENT_AFTER_TASK_DELETE,
			];

			if (in_array($eventType, $taskUpdatedEvents, true))
			{
				if (
					array_key_exists($taskId, $originData)
					&& $originData[$taskId]->getGroupId()
				)
				{
					$pushList[] = [
						'EVENT' => $eventType,
						'GROUP_ID' => $originData[$taskId]->getGroupId(),
					];
				}
				if (
					array_key_exists($taskId, $updatedData)
					&& $updatedData[$taskId]->getGroupId()
				)
				{
					$pushList[] = [
						'EVENT' => $eventType,
						'GROUP_ID' => $updatedData[$taskId]->getGroupId(),
					];
				}
			}
			elseif (in_array($eventType, $groupEventsForUsers, true))
			{
				$pushList[] = [
					'EVENT' => $eventType,
					'USER_IDS' => [$event->getUserId()],
					'GROUP_ID' => $groupId,
				];
			}
			elseif ($taskId)
			{
				if (in_array($eventType, $taskEventsForUsers, true))
				{
					$pushList[] = [
						'EVENT' => $eventType,
						'USER_IDS' => [$event->getUserId()],
						'TASK_ID' => $taskId,
					];
					continue;
				}
				$pushList[] = [
					'EVENT' => $eventType,
					'TASK_ID' => $taskId,
				];
			}
			elseif ($groupId)
			{
				$pushList[] = [
					'EVENT' => $eventType,
					'GROUP_ID' => $groupId,
				];
			}
		}

		$counterController = new CounterController();
		if (!empty($recountExpired))
		{
			$counterController->recount(CounterDictionary::COUNTER_GROUP_EXPIRED, array_unique($recountExpired));
		}
		if (!empty($recountComments))
		{
			$counterController->recount(CounterDictionary::COUNTER_GROUP_COMMENTS, array_unique($recountComments));
		}

		$updatedGroups = $this->getGroupByPerms($projectPermUpdated);
		if (!empty($updatedGroups))
		{
			$counterController->recount(CounterDictionary::COUNTER_GROUP_EXPIRED, [], $updatedGroups);
			$counterController->recount(CounterDictionary::COUNTER_GROUP_COMMENTS, [], $updatedGroups);
		}

		foreach ($updatedGroups as $groupId)
		{
			$pushList[] = [
				'EVENT' => EventDictionary::EVENT_PROJECT_PERM_UPDATE,
				'GROUP_ID' => $groupId
			];
		}

		if (!empty($personalPush))
		{
			(new PushSender())->sendUserCounters(array_unique($personalPush));
		}

		if (!empty($pushList))
		{
			(new GroupSender())->send($pushList);
		}
	}

	/**
	 * @param array $permIds
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function getGroupByPerms(array $permIds): array
	{
		if (empty($permIds))
		{
			return [];
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		$permIds = array_map(function ($el) {
			return (int) $el;
		}, $permIds);

		$sql = "
			SELECT SF.ENTITY_ID
			FROM ". FeaturePermTable::getTableName() ." SFP
			INNER JOIN ". FeatureTable::getTableName() ." SF
				ON SF.ID = SFP.FEATURE_ID
				AND SF.ENTITY_TYPE = '". FeatureTable::FEATURE_ENTITY_TYPE_GROUP ."'
			WHERE
				SFP.ID IN (". implode(",", $permIds) .")
		";

		$res = Application::getConnection()->query($sql);
		$rows = $res->fetchAll();

		$groupIds = [];
		foreach ($rows as $row)
		{
			$groupIds[] = $row['ENTITY_ID'];
		}

		return array_unique($groupIds);
	}

	/**
	 * @return EventResourceCollection
	 */
	private function getResourceCollection(): EventResourceCollection
	{
		return EventResourceCollection::getInstance();
	}
}