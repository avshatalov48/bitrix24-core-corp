<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Notification\Message;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;

class TooManyTasks
{
	public function getNotification(Message $message): ?Notification
	{
		$recipient = $message->getRecepient();

		$locKey = 'TASKS_ONBOARDING_TOO_MANY_TASKS';

		$notification = new Notification(
			$locKey,
			$message
		);

		$pathMaker = (new TaskPathMaker(ownerId: $recipient->getId()))
			->addQueryParam('apply_filter', 'Y')
			->addQueryParam('CREATED_BY', (string)$recipient->getId())
			->addQueryParam('RESPONSIBLE_ID', (string)$recipient->getId());

		$inWorkStatuses = Status::getInWorkStatuses();
		foreach ($inWorkStatuses as $i => $status)
		{
			$pathMaker->addQueryParam("STATUS[{$i}]", (string)$status);
		}

		$myTasksUrl = $pathMaker->makeEntitiesListPath();

		$notification->addTemplate(new Notification\Template('#MY_TASKS_URL#', $myTasksUrl));
		$notification->setParams(['action' => $locKey]);

		return $notification;
	}
}