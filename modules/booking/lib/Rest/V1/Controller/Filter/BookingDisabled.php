<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Controller\Filter;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class BookingDisabled extends Base
{
	public function onBeforeAction(Event $event): EventResult|null
	{
		if (Container::getIntranetBookingTool()->isDisabled())
		{
			$this->addError(
				ErrorBuilder::build('Booking tool is disabled. Please contact your administrator.')
			);

			return new EventResult(type: EventResult::ERROR, handler: $this);
		}

		return null;
	}
}
