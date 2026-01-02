<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParticipantTrait;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Integration\SocialNetwork\User;

class PushHandler
{
	use ConfigTrait;
	use ParticipantTrait;

	public function __invoke(array $fullTaskData): void
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}

		$pushRecipients = $this->getParticipants($fullTaskData);

		$groupId = (isset($taskData['GROUP_ID']) && $taskData['GROUP_ID'] > 0) ? (int)$taskData['GROUP_ID'] : 0;
		if ($groupId > 0)
		{
			$pushRecipients = array_unique(
				array_merge(
					$pushRecipients,
					User::getUsersCanPerformOperation($groupId, 'view_all')
				)
			);
		}

		$flowId = (isset($taskData['FLOW_ID']) && (int) $taskData['FLOW_ID']) ? (int) $taskData['FLOW_ID'] : 0;

		PushService::addEvent($pushRecipients, [
			'module_id' => 'tasks',
			'command' => PushCommand::TASK_DELETED,
			'params' => [
				'TASK_ID' => (int)$fullTaskData['ID'],
				'FLOW_ID' => $flowId,
				'TS' => time(),
				'event_GUID' => $this->config->getEventGuid(),
				'BEFORE' => [
					'GROUP_ID' => $groupId,
					'PARENT_ID' => (int)($fullTaskData['PARENT_ID'] ?? 0),
				],
			],
		]);
	}
}
