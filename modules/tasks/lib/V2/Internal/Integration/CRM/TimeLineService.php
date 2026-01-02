<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM;

use Bitrix\Tasks\Integration\CRM\TimeLineManager;

class TimeLineService
{
	public function viewTask(int $taskId, int $userId): void
	{
		TimeLineManager::get($taskId)
			->setUserId($userId)
			->onTaskViewed()
			->save();
	}

	public function viewComments(int $taskId, int $userId): void
	{
		TimeLineManager::get($taskId)
		->setUserId($userId)
		->onTaskAllCommentViewed()
		->save();
	}
}