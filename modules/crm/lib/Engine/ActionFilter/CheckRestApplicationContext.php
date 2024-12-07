<?php

namespace Bitrix\Crm\Engine\ActionFilter;

use Bitrix\Crm\Controller\Activity\Configurable;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Rest\OAuth\Auth;

class CheckRestApplicationContext extends Base
{
	public function onBeforeAction(Event $event): EventResult|null
	{
		$arguments = $this->getAction()->getArguments();

		foreach ($arguments as $argument)
		{
			if ($argument instanceof \CRestServer)
			{
				if (
					$argument->getClientId() === null
					|| $argument->getAuthType() !== Auth::AUTH_TYPE
				)
				{
					$this->addError(Configurable::getWrongContextError());

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
		}

		return null;
	}
}
