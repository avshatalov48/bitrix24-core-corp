<?php

namespace Bitrix\Tasks\Integration\Socialnetwork;

use Bitrix\Socialnetwork\Internals\EventService\EventDictionary;
use Bitrix\Socialnetwork\Internals\EventService\Service;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Counter\Event;

class SpaceService
{
	public function addEvent(string $type, array $data): void
	{
		// TODO: spaces stub
		return;
		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return;
		}

		// enrich payload
		$data['RECEPIENTS'] = $this->getRecipientIds($data);
		Service::addEvent($this->mapTaskToSpaceEvent($type), $data);
	}

	private function mapTaskToSpaceEvent(string $type): string
	{
		$map = [
			Event\EventDictionary::EVENT_AFTER_TASK_ADD => EventDictionary::EVENT_SPACE_TASK_ADD,
			Event\EventDictionary::EVENT_AFTER_TASK_UPDATE => EventDictionary::EVENT_SPACE_TASK_UPDATE,
			Event\EventDictionary::EVENT_AFTER_TASK_DELETE => EventDictionary::EVENT_SPACE_TASK_DELETE,
			Event\EventDictionary::EVENT_AFTER_COMMENT_ADD => EventDictionary::EVENT_SPACE_TASK_COMMENT_ADD,
			Event\EventDictionary::EVENT_AFTER_COMMENT_DELETE => EventDictionary::EVENT_SPACE_TASK_COMMENT_DELETE,
		];

		return $map[$type] ?? EventDictionary::EVENT_SPACE_TASKS_COMMON;
	}

	private function getRecipientIds(array $data): array
	{
		if (isset($data['TASK_ID']))
		{
			return $this->getTaskParticipantIds((int)$data['TASK_ID']);
		}

		if (isset($data['NEW_RECORD']['ID']))
		{
			return $this->getTaskParticipantIds((int)$data['NEW_RECORD']['ID']);
		}

		if (isset($data['CREATED_BY'], $data['RESPONSIBLE_ID']))
		{
			$recipients = [$data['CREATED_BY'], $data['RESPONSIBLE_ID']];
			if (isset($data['ACCOMPLICES']) && is_array($data['ACCOMPLICES']))
			{
				$recipients = array_merge($recipients, $data['ACCOMPLICES']);
			}

			if (isset($data['AUDITORS']) && is_array($data['AUDITORS']))
			{
				$recipients = array_merge($recipients, $data['AUDITORS']);
			}

			return array_unique(array_map(fn($recipient) => (int)$recipient, $recipients));
		}

		if (isset($data['USER_ID']))
		{
			// usually happens on readl/read_all events
			return [$data['USER_ID']];
		}

		return [];
	}

	private function getTaskParticipantIds(int $taskId): array
	{
		$recepients = [];
		$task = TaskRegistry::getInstance()->get($taskId, true);
		$memberList = $task['MEMBER_LIST'] ?? [];
		foreach($memberList as $member)
		{
			if (isset($member['USER_ID']))
			{
				$recepients[] = $member['USER_ID'];
			}
		}

		return array_values(array_unique($recepients));
	}
}