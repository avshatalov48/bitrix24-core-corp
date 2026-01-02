<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Event;

use Bitrix\Booking\Rest\V1\Event\Service\BookingRestService;
use Bitrix\Booking\Rest\V1\Event\Service\ResourceRestService;
use Bitrix\Booking\Rest\V1\Event\Service\ResourceTypeRestService;
use Bitrix\Booking\Rest\V1\Event\Service\RestServiceInterface;
use Bitrix\Booking\Rest\V1\Event\Service\WaitListItemRestService;

class RestServiceFactory
{
	/**
	 * @return RestServiceInterface[]
	 */
	public static function getServiceList(): array
	{
		return [
			ResourceTypeRestService::getInstance(),
			ResourceRestService::getInstance(),
			BookingRestService::getInstance(),
			WaitListItemRestService::getInstance(),
		];
	}
}
