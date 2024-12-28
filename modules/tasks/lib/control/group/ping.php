<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Notification\Controller;

class Ping
{
	public function runBatch(int $userId, array $taskIds): void
	{
		$registry = TaskRegistry::getInstance();
		$registry->load($taskIds, true);

		foreach ($taskIds as $id)
		{
			$task = $registry->getObject($id, true);

			if (!$task)
			{
				continue;
			}

			$taskData = $task->toArray(true);
			$commentPoster = CommentPoster::getInstance($id, $userId);
			if (!$commentPoster)
			{
				continue;
			}

			$commentPoster->postCommentsOnTaskStatusPinged($taskData);

			$controller = new Controller();
			$controller->onTaskPingSend($task, $userId);
			$controller->push();
		}
	}
}
