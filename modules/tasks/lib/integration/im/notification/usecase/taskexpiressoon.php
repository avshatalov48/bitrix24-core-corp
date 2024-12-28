<?php

namespace Bitrix\Tasks\Integration\IM\Notification\UseCase;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Integration\Extranet\User;
use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\UI;

class TaskExpiresSoon
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
				return $this->expiresSoonForResponsible($message, $task);
			case RoleDictionary::ROLE_ACCOMPLICE:
				return $this->expiresSoonForAccomplice($message, $task);
		}

		return null;
	}

	private function expiresSoonForResponsible(Message $message, TaskObject $task): Notification
	{
		$isHideEfficiencyPartNeeded = (
			$task->getResponsibleId() === $task->getCreatedBy()
			|| User::isExtranet($message->getRecepient()->getId())
		);

		$messageKey = $isHideEfficiencyPartNeeded
			? 'TASKS_TASK_EXPIRED_SOON_RESPONSIBLE_HIDE_EFFICIENCY_PART_MESSAGE'
			: 'TASKS_TASK_EXPIRED_SOON_RESPONSIBLE_MESSAGE'
		;

		return $this->createNotification($messageKey, $message, $task);
	}

	private function expiresSoonForAccomplice(Message $message, TaskObject $task): Notification
	{
		$isHideEfficiencyPartNeeded = (
			$message->getRecepient()->getId() === $task->getCreatedBy()
			|| User::isExtranet($message->getRecepient()->getId())
		);

		$messageKey = $isHideEfficiencyPartNeeded
			? 'TASKS_TASK_EXPIRED_SOON_RESPONSIBLE_HIDE_EFFICIENCY_PART_MESSAGE'
			: 'TASKS_TASK_EXPIRED_SOON_RESPONSIBLE_MESSAGE'
		;

		return $this->createNotification($messageKey, $message, $task);
	}

	private function createNotification(string $locKey, Message $message, TaskObject $task): Notification
	{
		$title = new Notification\Task\Title($task);
		$deadline = clone $task->getDeadline();
		$deadline->addSecond(\CTimeZone::GetOffset($message->getRecepient()->getId(), true));
		$formattedDeadline = $deadline->format(UI::getHumanTimeFormat($deadline->getTimestamp()));

		$notification = new Notification(
			$locKey,
			$message
		);

		$notification->setParams([
			'NOTIFY_EVENT' => 'task_expired_soon',
		]);

		$notification->addTemplate(
			new Notification\Template(
				'#TASK_TITLE#',
				$title->getFormatted($message->getRecepient()->getLang())
			)
		);

		$notification->addTemplate(
			new Notification\Template(
				'#DEADLINE_TIME#',
				$formattedDeadline
			)
		);

		return $notification;
	}
}