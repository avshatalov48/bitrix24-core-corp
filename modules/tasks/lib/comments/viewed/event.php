<?php

namespace Bitrix\Tasks\Comments\Viewed;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Counter;

class Event
{
	static public function prepare($fields): array
	{
		$role = $fields['ROLE'];

		$memberRole = null;
		if (
			$role
			&& array_key_exists($role, Counter\Role::ROLE_MAP)
		)
		{
			$memberRole = Counter\Role::ROLE_MAP[$role];
		}

		return [
			'ROLE' => $fields['ROLE'],
			'GROUP_ID' => (int)$fields['GROUP_ID'],
			'MEMBER_ROLE' => $memberRole,
			'CURRENT_USER_ID' => (int)CurrentUser::get()->getId()
		];
	}

	static public function addByTypeCounterService(string $type, array $fields): void
	{
		$groupId = $fields['GROUP_ID'];
		$memberRole = $fields['MEMBER_ROLE'];
		$currentUserId = $fields['CURRENT_USER_ID'];

		if ($type == Enum::USER)
		{
			Counter\CounterService::addEvent(
				Counter\Event\EventDictionary::EVENT_AFTER_COMMENTS_READ_ALL,
				[
					'USER_ID' => $currentUserId,
					'GROUP_ID' => $groupId,
					'ROLE' => $memberRole
				]
			);
		}
		else if ($type == Enum::PROJECT)
		{
			Counter\CounterService::addEvent(
				Counter\Event\EventDictionary::EVENT_AFTER_PROJECT_READ_ALL,
				[
					'USER_ID' => $currentUserId,
					'GROUP_ID' => $groupId
				]
			);
		}
		else if ($type == Enum::SCRUM)
		{
			Counter\CounterService::addEvent(
				Counter\Event\EventDictionary::EVENT_AFTER_SCRUM_READ_ALL,
				[
					'USER_ID' => $currentUserId,
					'GROUP_ID' => $groupId
				]
			);
		}
	}

	static public function addByTypePushService(string $type, array $fields): void
	{
		$role = $fields['ROLE'];
		$groupId = $fields['GROUP_ID'];
		$currentUserId = $fields['CURRENT_USER_ID'];

		if ($type == Enum::USER)
		{
			PushService::addEvent($currentUserId, [
				'module_id' => 'tasks',
				'command' => 'comment_read_all',
				'params' => [
					'USER_ID' => $currentUserId,
					'GROUP_ID' => $groupId,
					'ROLE' => $role,
				]
			]);
		}
		else if ($type == Enum::PROJECT)
		{
			PushService::addEvent($currentUserId, [
				'module_id' => 'tasks',
				'command' => 'project_read_all',
				'params' => [
					'USER_ID' => $currentUserId,
					'GROUP_ID' => $groupId,
				]
			]);
		}
		else if ($type == Enum::SCRUM)
		{
			PushService::addEvent($currentUserId, [
				'module_id' => 'tasks',
				'command' => 'scrum_read_all',
				'params' => [
					'USER_ID' => $currentUserId,
					'GROUP_ID' => $groupId,
				]
			]);
		}
	}
}