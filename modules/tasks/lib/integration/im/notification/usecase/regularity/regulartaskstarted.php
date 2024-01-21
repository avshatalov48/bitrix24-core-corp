<?php

namespace Bitrix\Tasks\Integration\IM\Notification\UseCase\Regularity;

use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;

class RegularTaskStarted
{
	public function getNotification(Message $message): ?Notification
	{
		$metadata = $message->getMetaData();
		$task = $metadata->getTask();
		$userRepository = $metadata->getUserRepository();

		if ($task === null || $userRepository === null)
		{
			return null;
		}

		$recipient = $message->getRecepient();

		$locKey = 'TASKS_REGULAR_TASK_STARTED';
		$description = '';

		$notification = new Notification(
			$locKey,
			$message
		);
		$title = new Notification\Task\Title($task);
		$notification->addTemplate(new Notification\Template('#TASK_TITLE#', $title->getFormatted($recipient->getLang())));
		$notification->addTemplate(new Notification\Template('#TASK_EXTRA#', $description));

		return $notification;
	}
}