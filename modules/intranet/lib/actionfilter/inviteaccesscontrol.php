<?php

namespace Bitrix\Intranet\ActionFilter;

use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Intranet;

class InviteAccessControl extends Engine\ActionFilter\Base
{
	public function onBeforeAction(Event $event)
	{
		if (!Intranet\Invitation::canCurrentUserInvite())
		{
			$this->addError(new Error(
				'Access denied.',
				'INTRANET_CONTROLLER_INVITE_NO_PERMISSIONS'
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}