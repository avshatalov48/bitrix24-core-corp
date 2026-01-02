<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Option\Action\Mute\Delete;

use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\V2\Internal\Entity;

class RunCounterEvent
{
	public function __invoke(Entity\Task\UserOption $userOption): void
	{
		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_TASK_MUTE,
			[
				'TASK_ID' => $userOption->taskId,
				'USER_ID' => $userOption->userId,
				'ADDED' => false,
			]
		);
	}
}