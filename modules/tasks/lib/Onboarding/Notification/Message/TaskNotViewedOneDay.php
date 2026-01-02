<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Notification\Message;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Onboarding\Internal\Type;

class TaskNotViewedOneDay
{
	public function getNotification(Message $message): ?Notification
	{
		$metaData = $message->getMetaData();
		$task = $metaData->getTask();
		$recipient = $message->getRecepient();

		$locKey = 'TASKS_ONBOARDING_ONE_DAY_NOT_VIEWED';

		$notification = new Notification(
			$locKey,
			$message
		);

		$title = new Notification\Task\Title($task);
		$notification->addTemplate(new Notification\Template('#TASK_TITLE#', $title->getFormatted($recipient->getLang())));
		$notification->setParams(['action' => $locKey]);

		$analyticsData = (new Notification\Analytics\AnalyticsData())
			->setSection(Analytics::SECTION['onboarding_notification'])
			->setElement(Analytics::ELEMENT['task_link'])
			->setEvent(Analytics::EVENT['click_task_link'])
			->setType(Analytics::NOTIFICATION_TYPE)
		;

		$notification->setAnalyticsData($analyticsData);

		return $notification;
	}
}
