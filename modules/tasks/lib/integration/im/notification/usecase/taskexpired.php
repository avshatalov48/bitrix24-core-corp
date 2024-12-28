<?php

namespace Bitrix\Tasks\Integration\IM\Notification\UseCase;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Integration\Extranet\User;
use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\TaskObject;

class TaskExpired
{
	public function getNotification(Message $message): ?Notification
	{
		$task = $message->getMetaData()->getTask();
		$memberCode = $message->getMetaData()->getMemberCode();

		if ($task === null)
		{
			return null;
		}

		switch ($memberCode)
		{
			case RoleDictionary::ROLE_RESPONSIBLE:
				return $this->expiredForResponsible($message, $task);
			case RoleDictionary::ROLE_DIRECTOR:
				return $this->expiredForCreator($message, $task);
			case RoleDictionary::ROLE_ACCOMPLICE:
				return $this->expiredForAccomplice($message, $task);
			case RoleDictionary::ROLE_AUDITOR:
				return $this->expiredForAuditor($message, $task);
		}

		return null;
	}

	private function expiredForResponsible(Message $message, TaskObject $task): Notification
	{
		$isHideEfficiencyPartNeeded = (
			$task->getResponsibleId() === $task->getCreatedBy()
			|| User::isExtranet($message->getRecepient()->getId())
		);

		$messageKey =
			$isHideEfficiencyPartNeeded
				? 'TASKS_TASK_EXPIRED_RESPONSIBLE_HIDE_EFFICIENCY_PART_MESSAGE'
				: 'TASKS_TASK_EXPIRED_RESPONSIBLE_MESSAGE'
		;

		return $this->createNotification($messageKey, $message, $task);
	}

	private function expiredForAccomplice(Message $message, TaskObject $task): Notification
	{
		$isHideEfficiencyPartNeeded = (
			$message->getRecepient()->getId() === $task->getCreatedBy()
			|| User::isExtranet($message->getRecepient()->getId())
		);

		$messageKey =
			$isHideEfficiencyPartNeeded
				? 'TASKS_TASK_EXPIRED_RESPONSIBLE_HIDE_EFFICIENCY_PART_MESSAGE'
				: 'TASKS_TASK_EXPIRED_RESPONSIBLE_MESSAGE'
		;

		return $this->createNotification($messageKey, $message, $task);
	}

	private function expiredForCreator(Message $message, TaskObject $task): Notification
	{
		$messageKey = 'TASKS_TASK_EXPIRED_CREATOR_MESSAGE';
		return $this->createNotification($messageKey, $message, $task);
	}

	private function expiredForAuditor(Message $message, TaskObject $task): Notification
	{
		$messageKey = 'TASKS_TASK_EXPIRED_AUDITOR_MESSAGE';
		return $this->createNotification($messageKey, $message, $task);
	}

	private function createNotification(string $locKey, Message $message, TaskObject $task): Notification
	{
		$title = new Notification\Task\Title($task);

		$notification = new Notification(
			$locKey,
			$message
		);

		$notification->setParams(['action' => 'TASK_EXPIRED']);

		$notification->addTemplate(
			new Notification\Template(
				'#TASK_TITLE#',
				$title->getFormatted($message->getRecepient()->getLang())
			)
		);

		return $notification;
	}
}