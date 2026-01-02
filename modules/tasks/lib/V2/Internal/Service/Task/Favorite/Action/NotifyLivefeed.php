<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Favorite\Action;

use Bitrix\Tasks\Integration\Socialnetwork\Task;

class NotifyLivefeed
{
	public function __invoke(
		int $taskId,
		int $userId,
		bool $isFavorite,
		bool $notifyLivefeed = true
	): void
	{
		if (!$notifyLivefeed)
		{
			return;
		}

		Task::toggleFavorites([
			'TASK_ID' => $taskId,
			'USER_ID' => $userId,
			'OPERATION' => $isFavorite ? 'ADD' : 'DELETE',
		]);
	}
}