<?php
namespace Bitrix\Tasks\Internals\Project\Pull;

use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Project\Event\Event;
use Bitrix\Tasks\Internals\Project\Event\EventTypeDictionary;

/**
 * Class PullSender
 *
 * @package Bitrix\Tasks\Internals\Project\Pull
 */
class PullSender
{
	public static function send(array $pullMap, array $notVisibleGroupsUsers): void
	{
		foreach ($pullMap as $command => $groupIds)
		{
			foreach ($groupIds as $groupId)
			{
				$groupKey = ($command === EventTypeDictionary::EVENT_PROJECT_USER_UPDATE ? 'GROUP_ID' : 'ID');
				$pushParams = [
					'module_id' => 'tasks',
					'command' => $command,
					'params' => [$groupKey => $groupId],
				];

				if (array_key_exists($groupId, $notVisibleGroupsUsers))
				{
					PushService::addEvent($notVisibleGroupsUsers[$groupId], $pushParams);
				}
				else
				{
					PushService::addEventByTag(PullDictionary::PULL_PROJECTS_TAG, $pushParams);
				}
			}
		}
	}

	public static function sendForUserAddedAndRemoved(Event $event, array $notVisibleGroupsUsers): void
	{
		$eventData = $event->getData();
		$groupId = $event->getGroupId();
		$pushParams = [
			'module_id' => 'tasks',
			'command' => $event->getType(),
			'params' => ['GROUP_ID' => $groupId],
		];

		if (array_key_exists($groupId, $notVisibleGroupsUsers))
		{
			if ($eventData['ROLE'] !== UserToGroupTable::ROLE_REQUEST)
			{
				if (!array_key_exists('USER_ID', $eventData))
				{
					$eventData['USER_ID'] = [];
				}
				if (!is_array($eventData['USER_ID']))
				{
					$eventData['USER_ID'] = [$eventData['USER_ID']];
				}
				$recipients = array_unique(array_merge($eventData['USER_ID'], $notVisibleGroupsUsers[$groupId]));
				PushService::addEvent($recipients, $pushParams);
			}
		}
		elseif ($eventData['ROLE'] === UserToGroupTable::ROLE_REQUEST)
		{
			PushService::addEvent($eventData['USER_ID'], $pushParams);
		}
		else
		{
			PushService::addEventByTag(PullDictionary::PULL_PROJECTS_TAG, $pushParams);
		}
	}
}