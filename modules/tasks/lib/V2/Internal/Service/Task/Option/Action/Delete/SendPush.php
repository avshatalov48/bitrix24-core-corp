<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Option\Action\Delete;

use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\V2\Internal\Entity;

class SendPush
{
	public function __invoke(Entity\Task\UserOption $userOption): void
	{
		PushService::addEvent([$userOption->userId], [
			'module_id' => 'tasks',
			'command' => PushCommand::USER_OPTION_UPDATED,
			'params' => [
				'TASK_ID' => $userOption->taskId,
				'USER_ID' => $userOption->userId,
				'OPTION' => $userOption->code,
				'ADDED' => false,
			],
		]);
	}
}