<?php

namespace Bitrix\Tasks\Integration\SocialNetwork\UseCase;

use Bitrix\Tasks\Integration\SocialNetwork\Log;
use Bitrix\Tasks\Internals\Notification\Message;

class TaskDeleted extends BaseCase
{
	public function execute(Message $message): void
	{
		$task = $message->getMetaData()->getTask();
		$safeDelete = $message->getMetaData()->getParams()['safe_delete'] ?? null;
		if ($safeDelete)
		{
			Log::hideLogByTaskId($task->getId());
			return;
		}

		Log::deleteLogByTaskId($task->getId());
	}
}