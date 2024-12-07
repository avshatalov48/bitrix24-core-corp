<?php

namespace Bitrix\Tasks\Integration\IM\Notification\UseCase;

use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\TaskObject;

class TaskStatusChanged
{
	public function getNotification(Message $message): ?Notification
	{
		$task = $message->getMetaData()->getTask();
		$taskCurrentStatus = $message->getMetaData()->getParams()['task_current_status'] ?? null;

		if ($task === null || $taskCurrentStatus === null)
		{
			return null;
		}

		$title = new Notification\Task\Title($task);
		$locKey = $this->getTaskStatusMessageKey($taskCurrentStatus, $task);

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

		if ($taskCurrentStatus == Status::DECLINED)
		{
			$notification->addTemplate(
				new Notification\Template(
					'#TASK_DECLINE_REASON#',
					$task->getDeclineReason()
				)
			);
		}

		return $notification;
	}

	private function getTaskStatusMessageKey(int $taskCurrentStatus, TaskObject $task): string
	{
		// default message key
		$messageKey = 'TASKS_TASK_STATUS_MESSAGE_' . $taskCurrentStatus;

		if (
			($taskCurrentStatus === Status::NEW || $taskCurrentStatus === Status::PENDING)
			&& ($task->getRealStatus() === Status::SUPPOSEDLY_COMPLETED)
		)
		{
			$messageKey = 'TASKS_TASK_STATUS_MESSAGE_REDOED';
		}
		elseif ($taskCurrentStatus === Status::PENDING && $task->getRealStatus() === Status::DEFERRED)
		{
			$messageKey = 'TASKS_TASK_STATUS_MESSAGE_1';
		}

		return (new Notification\MessageKey($messageKey))->getWithVersion();
	}
}