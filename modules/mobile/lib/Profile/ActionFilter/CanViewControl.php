<?php

namespace Bitrix\Mobile\Profile\ActionFilter;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Mobile\Profile\Provider\ProfileProvider;
use Bitrix\Main\Engine\ActionFilter\Base;

class CanViewControl extends Base
{
	public function onBeforeAction(Event $event): EventResult|null
	{
		$viewerId = $this->getAction()->getCurrentUser()?->getId();
		$arguments = $this->getAction()->getArguments();
		if (empty($arguments['ownerId']))
		{
			$this->addError(new Error('ownerId is required'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}
		$ownerId = (int)$arguments['ownerId'];
		if (!(new ProfileProvider($viewerId, $ownerId))->canView())
		{
			$this->addError(new Error('No permissions to view profile'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}