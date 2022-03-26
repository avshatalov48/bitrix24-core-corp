<?php

namespace Bitrix\Tasks\Action\Filter;

use Bitrix\Main\Event;

class BooleanFilter extends \Bitrix\Main\Engine\ActionFilter\Base
{
	public function onBeforeAction(Event $event)
	{
		$httpRequest = $this->getAction()->getController()->getRequest();
		if ($httpRequest && $httpRequest->isPost())
		{
			$httpRequest->addFilter(new BooleanPostFilter());
		}

		return null;
	}
}