<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Filter;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\Yandex\InternalErrorException;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

// TODO: filter can be common for whole bookingservice api
class BookingDisabled extends Base
{
	public function onBeforeAction(Event $event): EventResult|null
	{
		if (Container::getIntranetBookingTool()->isDisabled())
		{
			$exception = new InternalErrorException();
			$this->addError(
				new Error(
					$exception->getMessage(),
					$exception->getExternalCode(),
				)
			);

			return new EventResult(type: EventResult::ERROR, handler: $this);
		}

		return null;
	}
}
