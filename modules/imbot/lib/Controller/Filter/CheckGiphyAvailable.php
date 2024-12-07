<?php

namespace Bitrix\ImBot\Controller\Filter;

use Bitrix\ImBot\Bot\Giphy;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CheckGiphyAvailable extends Base
{
	private const REGION_ERROR = 'REGION_ERROR';

	public function onBeforeAction(Event $event)
	{
		if (!Giphy::isAvailable())
		{
			$this->addError(new Error('Service not available in this region', self::REGION_ERROR));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}