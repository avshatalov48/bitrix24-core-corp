<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\SocialNetwork\Collab\Counter;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\Entity\Event\EventDispatcher;
use Bitrix\Socialnetwork\Collab\Registry\CollabRegistry;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\Event\Event;
use Bitrix\Tasks\Internals\Counter\Event\EventResourceCollection;
use Bitrix\Tasks\Internals\Counter\Event\EventCollection;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;

class CollabListener
{
	public function notify(EventResourceCollection $resource, EventCollection $events): void
	{
		if (!$this->isEnabled())
		{
			return;
		}

		$eventList = $events->list();

		if (empty($eventList))
		{
			return;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$taskIds = [];
		foreach ($eventList as $event)
		{
			/** @var Event $event */
			$groupId = $event->getRawGroupId();
			if ($groupId <= 0)
			{
				continue;
			}

			$collab = CollabRegistry::getInstance()->get($groupId);
			if ($collab === null)
			{
				continue;
			}

			$taskIds[$groupId][] = $event->getTaskId();
		}

		foreach ($taskIds as $groupId => $ids)
		{
			$ids = array_unique($ids);
			$memberIds = $this->getTasksMembers($resource, ...$ids);

			$this->notifyDispatcher($groupId, $memberIds);
		}
	}

	private function notifyDispatcher(int $groupId, array $userIds): void
	{
		$counters = [];
		foreach ($userIds as $userId)
		{
			$counter = Counter::getInstance($userId)->get(CounterDictionary::COUNTER_MEMBER_TOTAL, $groupId);
			$counters[$userId] = $counter;
		}

		// instant notification to avoid sql-query via events
		EventDispatcher::onCountersRecount($groupId, $counters, 'tasks');
	}

	private function getTasksMembers(EventResourceCollection $resource, int ...$taskIds): array
	{
		if (
			empty($taskIds)
			|| (count($taskIds) === 1 && $taskIds[0] === 0)
		)
		{
			return [];
		}

		$originData = $resource->getOrigin();
		$updatedData = $resource->getModified();

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
				$members[(int)$userId] = true;
			}
		}

		return array_keys($members);
	}

	private function isEnabled(): bool
	{
		return Option::get('tasks', 'tasks_collab_listener', 'Y') === 'Y';
	}
}