<?php

namespace Bitrix\Mobile\Profile\ActionFilter;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Mobile\Profile\Provider\ProfileProvider;
use Bitrix\Main\Engine\ActionFilter\Base;

class NewProfileEnabledControl extends Base
{
	public function onBeforeAction(Event $event): EventResult|null
	{
		if (!ProfileProvider::isNewProfileFeatureEnabled())
		{
			$this->addError(new Error('New profile is not available'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}