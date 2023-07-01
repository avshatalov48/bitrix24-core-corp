<?php
namespace Bitrix\ImConnector\Controller\Filter;

use Bitrix\ImConnector\Controller\Openlines;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class Connector extends Base
{
	public function onBeforeAction(Event $event)
	{
		$arguments = $this->getAction()->getArguments();

		if (!in_array($arguments['connectorId'], array_keys(Openlines::CONNECTORS)))
		{
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
