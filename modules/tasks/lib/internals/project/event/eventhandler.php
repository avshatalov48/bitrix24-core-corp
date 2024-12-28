<?php

namespace Bitrix\Tasks\Internals\Project\Event;

use Bitrix\Main\Application;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Internals\Project\Pull;

/**
 * Class EventHandler
 *
 * @package Bitrix\Tasks\Internals\Project\Event
 */
class EventHandler
{
	private static $instance;
	private static $isJobIsOn = false;

	private $oldFields = [];
	private $newFields = [];

	private $registry;

	private function __construct()
	{

	}

	public static function getInstance(): EventHandler
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function addEvent(string $type, array $data): void
	{
		$event = new Event($type, $data);

		self::getInstance()->collectOldData($event);
		self::getInstance()->registerEvent($event);
		self::getInstance()->addBackgroundJob();
	}

	private function collectOldData(Event $event): void
	{
		$groupId = $event->getGroupId();
		if ($groupId && !array_key_exists($groupId, $this->oldFields))
		{
			$this->oldFields[$groupId] = \CSocNetGroup::getById($groupId);
		}
	}

	private function registerEvent(Event $event): void
	{
		$this->registry[] = $event;
	}

	private function addBackgroundJob(): void
	{
		if (self::$isJobIsOn)
		{
			return;
		}

		$application = Application::getInstance();
		$application && $application->addBackgroundJob([__CLASS__, 'process'], [], (Application::JOB_PRIORITY_LOW - 2));

		self::$isJobIsOn = true;
	}

	public static function process(): void
	{
		self::getInstance()->collectNewData();
		self::getInstance()->handleEvents();
	}

	private function collectNewData(): void
	{
		/** @var Event $event */
		foreach ($this->registry as $event)
		{
			$groupId = $event->getGroupId();
			if ($groupId && !array_key_exists($groupId, $this->newFields))
			{
				$this->newFields[$groupId] = \CSocNetGroup::getById($groupId);
			}
		}
	}

	private function handleEvents(): void
	{
		$addedEventTypes = [
			EventTypeDictionary::EVENT_PROJECT_ADD,
		];
		$changedEventTypes = [
			EventTypeDictionary::EVENT_PROJECT_BEFORE_UPDATE,
			EventTypeDictionary::EVENT_PROJECT_UPDATE,
		];
		$removedEventTypes = [
			EventTypeDictionary::EVENT_PROJECT_REMOVE,
		];
		$userChangedEventTypes = [
			EventTypeDictionary::EVENT_PROJECT_USER_ADD,
			EventTypeDictionary::EVENT_PROJECT_USER_UPDATE,
			EventTypeDictionary::EVENT_PROJECT_USER_REMOVE,
		];
		$userAddedAndRemovedEventTypes = [
			EventTypeDictionary::EVENT_PROJECT_USER_ADD,
			EventTypeDictionary::EVENT_PROJECT_USER_REMOVE,
		];

		$added = $this->getGroupIdsByEventTypes($addedEventTypes);
		$changed = $this->getGroupIdsByEventTypes($changedEventTypes);
		$removed = $this->getGroupIdsByEventTypes($removedEventTypes);
		$userChanged = $this->getGroupIdsByEventTypes($userChangedEventTypes);

		$added = array_diff($added, $removed);
		$changed = array_diff($changed, $added, $removed);
		$changed = $this->clearNotRealChanges($changed);
		$userChanged = array_diff($userChanged, $added, $changed, $removed);

//		[$added, $changed, $removed, $userChanged] = $this->clearNotProjects([$added, $changed, $removed, $userChanged]);
		$notVisibleGroupsUsers = $this->getNotVisibleGroupsUsers([$added, $changed, $removed, $userChanged]);

		/** @var Event $event */
		foreach ($this->registry as $event)
		{
			$eventType = $event->getType();
			$groupId = $event->getGroupId();

			if (
				in_array($eventType, $userAddedAndRemovedEventTypes, true)
				&& in_array($groupId, $userChanged, true)
			)
			{
				Pull\PullSender::sendForUserAddedAndRemoved($event, $notVisibleGroupsUsers);
				unset($userChanged[$groupId]);
			}
		}

		$pullMap = [
			EventTypeDictionary::EVENT_PROJECT_UPDATE => $changed,
			EventTypeDictionary::EVENT_PROJECT_USER_UPDATE => $userChanged,
			EventTypeDictionary::EVENT_PROJECT_ADD => $added,
			EventTypeDictionary::EVENT_PROJECT_REMOVE => $removed,
		];
		Pull\PullSender::send($pullMap, $notVisibleGroupsUsers);
	}

	private function getGroupIdsByEventTypes(array $eventTypes): array
	{
		$groupIds = [];

		/** @var Event $event */
		foreach ($this->registry as $event)
		{
			if (in_array($event->getType(), $eventTypes, true))
			{
				$groupId = $event->getGroupId();
				$groupIds[$groupId] = $groupId;
			}
		}

		return $groupIds;
	}

	private function clearNotRealChanges(array $changed): array
	{
		$realChanges = [
			'NAME',
			'PROJECT_DATE_START',
			'PROJECT_DATE_FINISH',
			'IMAGE_ID',
			'AVATAR_TYPE',
			'OPENED',
			'CLOSED',
			'VISIBLE',
			'PROJECT',
			'KEYWORDS',
		];

		foreach ($changed as $groupId)
		{
			if (!is_array($this->oldFields[$groupId]) || !$this->newFields[$groupId])
			{
				continue;
			}

			$changes = $this->getChanges($this->oldFields[$groupId], $this->newFields[$groupId]);
			if (!array_intersect_key($changes, array_flip($realChanges)))
			{
				unset($changed[$groupId]);
			}
		}

		return $changed;
	}

	private function getChanges(array $oldFields, array $newFields): array
	{
		$changes = [];

		foreach ($newFields as $key => $value)
		{
			if (mb_strpos($key, '~') === 0)
			{
				continue;
			}
			if (array_key_exists($key, $oldFields) && $oldFields[$key] !== $value)
			{
				$changes[$key] = $value;
			}
		}

		return $changes;
	}

	private function clearNotProjects(array $groups): array
	{
		[$added, $changed, $removed, $userChanged] = $groups;

		$filter = function ($groupId) {
			return ($this->newFields[$groupId]['PROJECT'] === 'Y');
		};
		$changedFilter = function ($groupId) {
			return ($this->newFields[$groupId]['PROJECT'] === 'Y' || $this->oldFields[$groupId]['PROJECT'] === 'Y');
		};
		$removedFilter = function ($groupId) {
			return ($this->oldFields[$groupId]['PROJECT'] === 'Y');
		};

		return [
			array_filter($added, $filter),
			array_filter($changed, $changedFilter),
			array_filter($removed, $removedFilter),
			array_filter($userChanged, $filter),
		];
	}

	private function getNotVisibleGroupsUsers(array $groups): array
	{
		$users = [];

		if (empty($notVisibleGroupIds = $this->getNotVisibleGroupIds($groups)))
		{
			return $users;
		}

		$dbResult = UserToGroupTable::getList([
			'select' => ['GROUP_ID', 'USER_ID'],
			'filter' => ['@GROUP_ID' => $notVisibleGroupIds],
		]);
		while ($item = $dbResult->fetch())
		{
			$users[$item['GROUP_ID']][] = $item['USER_ID'];
		}

		return $users;
	}

	private function getNotVisibleGroupIds($groups): array
	{
		[$added, $changed, $removed, $userChanged] = $groups;

		$filter = function($groupId) {
			return ($this->newFields[$groupId]['VISIBLE'] === 'N');
		};
		$changedFilter = function ($groupId) {
			return ($this->newFields[$groupId]['VISIBLE'] === 'N' && $this->oldFields[$groupId]['VISIBLE'] === 'N');
		};
		$removedFilter = function ($groupId) {
			return ($this->oldFields[$groupId]['VISIBLE'] === 'N');
		};

		return array_merge(
			array_filter($added, $filter),
			array_filter($changed, $changedFilter),
			array_filter($removed, $removedFilter),
			array_filter($userChanged, $filter)
		);
	}
}