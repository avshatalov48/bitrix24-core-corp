<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Main\Event;
use Bitrix\Tasks\V2\Internal\Entity\Task;

class RunInternalEvent
{
	public function __invoke(Task $before, Task $after): void
	{
		$event = new Event('tasks', 'onTaskUpdateInternal', [
			'before' => $before,
			'after' => $after,
		]);

		$event->send();
	}
}