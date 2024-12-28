<?php

namespace Bitrix\Tasks\Integration\IM\Notification\UseCase\Flow;

use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;

class TaskAddedToFlowWithHimselfDistribution
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
		$flowName = Emoji::decode($flow->getName());
		$recipient = $message->getRecepient();

		$flowUrl = "/company/personal/user/{$recipient->getId()}/tasks/flow/?apply_filter=Y&ID_numsel=exact&ID_from={$flow->getId()}";

		$locKey = 'TASKS_ADDED_TO_FLOW_WITH_HIMSELF_DISTRIBUTION';

		$notification = new Notification(
			$locKey,
			$message
		);
		$notification->setParams(['action' => $locKey]);
		$notification->addTemplate(new Notification\Template('#FLOW_URL#', $flowUrl));
		$notification->addTemplate(new Notification\Template('#TASK_TITLE#', $taskTitle->getFormatted($recipient->getLang())));
		$notification->addTemplate(new Notification\Template('#FLOW_NAME#', $flowName));

		return $notification;
	}
}