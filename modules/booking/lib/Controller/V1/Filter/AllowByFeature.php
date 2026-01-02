<?php

namespace Bitrix\Booking\Controller\V1\Filter;

use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Service\BookingFeature;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\EventResult;
use Bitrix\Main\Event;

class AllowByFeature extends Base
{
	public function onBeforeAction(Event $event): EventResult|null
	{
		if (!BookingFeature::isFeatureEnabled())
		{
			$this->addError(ErrorBuilder::build('Access denied'));

			return new EventResult(type: EventResult::ERROR, handler: $this);
		}

		return null;
	}
}
