<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Tasks\Integration\Bizproc\Listener;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class AutomationService
{
	public function onTaskFieldChanged(int $taskId, array $updatedFields): void
	{
		$task = TaskRegistry::getInstance()->getObject($taskId);

		if (!$task)
		{
			return;
		}

		$task->fillMemberList();

		Listener::onTaskFieldChanged(
			$task->getId(),
			$updatedFields,
			$task->collectValues()
		);
	}
}
