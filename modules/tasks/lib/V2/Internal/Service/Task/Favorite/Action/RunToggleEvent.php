<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Favorite\Action;

use Bitrix\Main\Event;

class RunToggleEvent
{
	public function __invoke(int $taskId, int $userId, bool $isFavorite): void
	{
		$event = new Event('tasks', 'OnTaskToggleFavorite', [$taskId, $userId, $isFavorite]);
		$event->send();
	}
}