<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Push;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Internals\Counter\CounterState;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\Project;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class GroupSender
{

	private static $subscribers = [];

	public function __construct()
	{

	}

	/**
	 * @param array $pushList
	 */
	public function send(array $pushList)
	{
		if (!ModuleManager::isModuleInstalled('pull') || !Loader::includeModule('pull'))
		{
			return;
		}

		$pushList = $this->fillPushGroup($pushList);

		$this->sendPersonalPush($pushList);

		$pushList = $this->rearrangePushList($pushList);

		$sender = new PushSender();
		foreach ($pushList as $push)
		{
			$groupId = (int)$push['GROUP_ID'];
			if (!array_key_exists('USER_IDS', $push))
			{
				$userIds = $this->getUsersToPush($groupId);
			}
			else
			{
				$userIds = $push['USER_IDS'];
			}

			if (empty($userIds))
			{
				continue;
			}

			$sender->createPush(
				$userIds,
				PushSender::COMMAND_PROJECT,
				[
					'GROUP_ID' => $groupId,
					'EVENT' => $push['EVENT']
				]
			);
		}
	}

	/**
	 * @param array $pushList
	 */
	private function sendPersonalPush(array $pushList): void
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$groupIds = [];

		$owners = [];

		foreach ($pushList as $push)
		{
			if (!in_array($push['EVENT'], [
				EventDictionary::EVENT_PROJECT_DELETE,
				EventDictionary::EVENT_PROJECT_PERM_UPDATE,
				EventDictionary::EVENT_AFTER_TASK_ADD,
				EventDictionary::EVENT_AFTER_TASK_RESTORE,
				EventDictionary::EVENT_AFTER_TASK_UPDATE,
				EventDictionary::EVENT_TASK_EXPIRED,
				EventDictionary::EVENT_AFTER_TASK_MUTE
			]))
			{
				continue;
			}

			if (
				$push['EVENT'] === EventDictionary::EVENT_AFTER_PROJECT_READ_ALL
				|| $push['EVENT'] === EventDictionary::EVENT_AFTER_SCRUM_READ_ALL
			)
			{
				$owners[] = $push['USER_ID'];
			}

			if (!$push['GROUP_ID'])
			{
				continue;
			}

			$groupIds[] = $push['GROUP_ID'];
		}

		$groupIds = array_unique($groupIds);
		if (empty($groupIds))
		{
			return;
		}

		$owners = array_unique(array_merge($owners, $this->getProjectsOwners($groupIds)));
		foreach ($owners as $userId)
		{
			CounterState::reload($userId);
		}
		(new PushSender())->sendUserCounters($owners);
	}

	/**
	 * @param int $groupId
	 * @return array
	 */
	private function getProjectsOwners(array $groupId): array
	{
		$owners = [];

		$res = UserToGroupTable::query()
			->setSelect([
				'USER_ID'
			])
			->setFilter([
				'@GROUP_ID' => $groupId,
				'@ROLE' => [UserToGroupTable::ROLE_OWNER, UserToGroupTable::ROLE_MODERATOR]
			])
			->fetchCollection();

		foreach ($res as $row)
		{
			$owners[] = (int)$row['USER_ID'];
		}

		return array_unique($owners);
	}

	/**
	 * @param int $groupId
	 * @return array
	 */
	private function getUsersToPush(int $groupId): array
	{
		$subscribers = array_merge($this->getPushSubscribers(), $this->getPushSubscribers($groupId));
		return array_unique(array_values($subscribers));
	}

	/**
	 * @param int $groupId
	 * @return array
	 */
	private function getPushSubscribers(int $groupId = 0): array
	{
		$key = Project\Pull\PullDictionary::PULL_PROJECTS_TAG;
		if ($groupId)
		{
			$key .= '_'.$groupId;
		}

		if (!array_key_exists($key, self::$subscribers))
		{
			self::$subscribers[$key] = \CPullWatch::GetUserList($key);
		}

		return self::$subscribers[$key];
	}

	/**
	 * @param array $pushList
	 * @return array
	 */
	private function fillPushGroup(array $pushList): array
	{
		$res = [];
		foreach ($pushList as $push)
		{
			if (
				array_key_exists('GROUP_ID', $push)
				&& $push['GROUP_ID'] > 0
			)
			{
				$res[] = $push;
				continue;
			}

			if (array_key_exists('TASK_ID', $push))
			{
				$task = TaskRegistry::getInstance()->get((int)$push['TASK_ID']);
				$push['GROUP_ID'] = $task['GROUP_ID'];
			}

			if (!isset($push['GROUP_ID']))
			{
				continue;
			}

			$res[] = $push;
		}
		return $res;
	}

	/**
	 * @param array $pushList
	 * @return array
	 */
	private function rearrangePushList(array $pushList): array
	{
		$groupedByGroup = $this->getGroupedByGroup($pushList);
		$groupedByGroup = $this->clearRedundantGroupEvents($groupedByGroup);

		$result = [];
		foreach ($groupedByGroup as $groupId => $events)
		{
			foreach ($events as $event)
			{
				$event['GROUP_ID'] = $groupId;
				$result[] = $event;
			}
		}

		return $result;
	}

	private function getGroupedByGroup(array $events): array
	{
		$groupedByGroup = [];

		foreach ($events as $event)
		{
			$groupId = $event['GROUP_ID'];

			if (!array_key_exists($groupId, $groupedByGroup))
			{
				$groupedByGroup[$groupId] = [];
			}

			if (isset($event['USER_IDS']))
			{
				$groupedByGroup[$groupId][] = $event;
				continue;
			}

			$key = $event['EVENT'] . $groupId;
			if (!array_key_exists($key, $groupedByGroup[$groupId]))
			{
				$groupedByGroup[$groupId][$key] = $event;
			}
		}

		return $groupedByGroup;
	}

	private function clearRedundantGroupEvents(array $groupedByGroup): array
	{
		$projectMovingEvents = [
			EventDictionary::EVENT_AFTER_TASK_ADD,
			EventDictionary::EVENT_AFTER_COMMENT_ADD,
		];
		foreach ($groupedByGroup as $groupId => $events)
		{
			if (count($events) <= 1)
			{
				continue;
			}

			$eventsWithoutUsers = $this->getEventsWithoutUsers($events);
			if (count($eventsWithoutUsers) <= 0)
			{
				$groupedByUser = $this->getGroupedByUser($events);
				$groupedByGroup[$groupId] = $this->clearRedundantUserEvents($groupedByUser);
			}
			else
			{
				foreach ($eventsWithoutUsers as $event)
				{
					if (in_array($event['EVENT'], $projectMovingEvents, true))
					{
						$groupedByGroup[$groupId] = [$event];
						continue 2;
					}
				}
				$groupedByGroup[$groupId] = [current($eventsWithoutUsers)];
			}
		}

		return $groupedByGroup;
	}

	private function getEventsWithoutUsers(array $events): array
	{
		return array_filter(
			$events,
			static function ($event) {
				return !array_key_exists('USER_IDS', $event);
			}
		);
	}

	private function getGroupedByUser(array $events): array
	{
		$groupedByUser = [];

		foreach ($events as $event)
		{
			$eventType = $event['EVENT'];
			$userIds = $event['USER_IDS'];

			foreach ($userIds as $userId)
			{
				if (!array_key_exists($userId, $groupedByUser))
				{
					$groupedByUser[$userId] = [];
				}
				$groupedByUser[$userId][$eventType] = $eventType;
			}
		}

		return $groupedByUser;
	}

	private function clearRedundantUserEvents(array $groupedByUser): array
	{
		$result = [];

		foreach ($groupedByUser as $userId => $userEvents)
		{
			$groupedByUser[$userId] = current($userEvents);
		}

		$uniqueEvents = array_unique($groupedByUser);
		foreach ($uniqueEvents as $event)
		{
			$result[] = [
				'EVENT' => $event,
				'USER_IDS' => array_keys($groupedByUser, $event),
			];
		}

		return $result;
	}
}