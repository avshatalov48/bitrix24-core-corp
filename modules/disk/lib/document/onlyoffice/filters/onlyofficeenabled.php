<?php

namespace Bitrix\Disk\Document\OnlyOffice\Filters;

use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class OnlyOfficeEnabled extends ActionFilter\Base
{
	public function onBeforeAction(Event $event)
	{
		if (!OnlyOfficeHandler::isEnabled())
		{
			$this->addError(new Error('OnlyOffice handler is not configured and not enabled.'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}