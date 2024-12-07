<?php

namespace Bitrix\SignMobile\Service;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\SignMobile\Service;
use Bitrix\SignMobile\Repository;

class Container
{
	public static function instance(): Container
	{
		return self::getService('signmobile.container');
	}

	public function getEventService(): Service\EventService
	{
		return static::getService('signmobile.service.event');
	}

	public function getNotificationRepository(): Repository\NotificationRepository
	{
		return static::getService('signmobile.repository.notification');
	}

	public function getNotificationPriorityQueueRepository(): Repository\NotificationPriorityQueueRepository
	{
		return static::getService('signmobile.repository.notification.priority.queue');
	}

	private static function getService(string $name): mixed
	{
		$prefix = 'signmobile.';
		if (mb_strpos($name, $prefix) !== 0)
		{
			$name = $prefix . $name;
		}
		$locator = ServiceLocator::getInstance();
		return $locator->has($name)
			? $locator->get($name)
			: null
			;
	}
}