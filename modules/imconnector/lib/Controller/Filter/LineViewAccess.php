<?php
namespace Bitrix\ImConnector\Controller\Filter;

use Bitrix\ImConnector\Controller\Openlines;
use Bitrix\ImOpenLines\Config;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class LineViewAccess extends Base
{
	public function onBeforeAction(Event $event)
	{
		$arguments = $this->getAction()->getArguments();

		if (!Config::canViewLine($arguments['configId']))
		{
			$this->addError(new Error(
				Openlines::ERROR_ACCESS_DENIED['message'],
				Openlines::ERROR_ACCESS_DENIED['code']
			));
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
