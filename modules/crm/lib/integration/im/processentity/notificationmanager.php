<?php

namespace Bitrix\Crm\Integration\Im\ProcessEntity;

use Bitrix\Crm\Integration\Im\ProcessEntity\Notification\Observer;
use Bitrix\Crm\Integration\Im\ProcessEntity\Notification\Responsible;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Crm\Comparer\Difference;

class NotificationManager
{
	use Singleton;

	public function getNotificationClasses(): array
	{
		return [
			Responsible::class,
			Observer::class,
		];
	}

	public function sendAllNotifications(
		int $entityTypeId,
		Difference $difference,
		string $sendingType,
	): void
	{
		$notificationClasses = $this->getNotificationClasses();
		foreach ($notificationClasses as $notificationClass)
		{
			/** @var Notification $notification */
			$notification = new $notificationClass(
				$entityTypeId,
				$difference,
				$sendingType,
			);

			$notification->send();
		}
	}

	public function sendAllNotificationsAboutAdd(
		int $entityTypeId,
		Difference $difference,
	): void
	{
		$this->sendAllNotifications(
			$entityTypeId,
			$difference,
			Notification::ADD_SENDING_TYPE
		);
	}

	public function sendAllNotificationsAboutUpdate(
		int $entityTypeId,
		Difference $difference,
	): void
	{
		$this->sendAllNotifications(
			$entityTypeId,
			$difference,
			Notification::UPDATE_SENDING_TYPE
		);
	}
}
