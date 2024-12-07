<?php

namespace Bitrix\Tasks\Integration\IM\Notification\UseCase\Flow;

use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;

class TaskAddedToFlowWithManualDistribution
{
	public function getNotification(Message $message): ?Notification
	{
		$metadata = $message->getMetaData();
		$task = $metadata->getTask();
		$userRepository = $metadata->getUserRepository();
		/** @var FlowEntity $flow */
		$flow = $metadata->getParams()['flow'] ?? null;

		if ($task === null || $userRepository === null || $flow === null)
		{
			return null;
		}

		$taskTitle = new Notification\Task\Title($task);
		$flowName = \Bitrix\Main\Text\Emoji::decode($flow->getName());
		$recipient = $message->getRecepient();

		$locKey = 'TASKS_ADDED_TO_FLOW_WITH_MANUAL_DISTRIBUTION';

		$notification = new Notification(
			$locKey,
			$message
		);
		$notification->setParams(['action' => $locKey]);
		$notification->addTemplate(new Notification\Template('#TASK_TITLE#', $taskTitle->getFormatted($recipient->getLang())));
		$notification->addTemplate(new Notification\Template('#FLOW_NAME#', $flowName));
		$notification->addTemplate(new Notification\Template('#RECEPIENT_ID#', $recipient->getId()));
		$notification->addTemplate(new Notification\Template('#FLOW_ID#', $flow->getId()));

		return $notification;
	}
}