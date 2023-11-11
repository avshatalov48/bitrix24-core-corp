<?php

namespace Bitrix\Tasks\Integration\AI\Event\Factory;

use Bitrix\Tasks\Integration\AI\event\EventControllerInterface;
use Bitrix\Tasks\Integration\AI\Event\TaskCommentEventController;

class EventControllerFactory
{
	public static function getController(string $context, int $taskId, string $xmlId): ?EventControllerInterface
	{
		return match ($context)
		{
			'comments' => new TaskCommentEventController($taskId, $xmlId),
			default => null,
		};
	}
}
