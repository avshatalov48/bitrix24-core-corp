<?php

namespace Bitrix\Tasks\Integration\IM\Notification\UseCase;

use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;

class TaskDeleted
{
	public function getNotification(Message $message): ?Notification
	{
		$task = $message->getMetaData()->getTask();

		if ($task === null)
		{
			return null;
		}

		$title = new Notification\Task\Title($task);
		$locKey = 'TASKS_TASK_DELETED_MESSAGE_V2';

		$notification = new Notification(
			$locKey,
			$message
		);

		$notification->addTemplate(
			new Notification\Template(
				'#TASK_TITLE#',
				$title->getFormatted($message->getRecepient()->getLang())
			)
		);

		return $notification;
	}
}