<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Event;

use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use CRestUtil;

class RestEventHandler
{
	private const MODULE_ID = 'booking';

	public static function onRestServiceBuildDescription(): array
	{
		return [
			self::MODULE_ID => [
				CRestUtil::EVENTS => self::getEvents(),
			],
		];
	}

	private static function getEvents(): array
	{
		$eventList = [];

		foreach (RestServiceFactory::getServiceList() as $service)
		{
			foreach ($service->getEvents() as $code => $event)
			{
				if (isset($eventList[$code]))
				{
					throw new InvalidArgumentException("Duplicate event code: {$code}");
				}

				$eventList[$code] = $event;
			}
		}

		return $eventList;
	}
}
