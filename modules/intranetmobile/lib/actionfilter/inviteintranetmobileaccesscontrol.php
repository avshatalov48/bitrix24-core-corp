<?php

namespace Bitrix\IntranetMobile\ActionFilter;

use Bitrix\Main;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Intranet;

class InviteIntranetMobileAccessControl extends Engine\ActionFilter\Base
{
	public function onBeforeAction(Event $event): EventResult|null
	{
		if (!$this->isPublicAction($event) && !Intranet\Invitation::canCurrentUserInvite())
		{
			$this->addError(new Error(
				Main\Localization\Loc::getMessage('INTRANETMOBILE_INVITE_ACCESS_CONTROL_ACCESS_DENIED')
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	private function isPublicAction(Event $event):  bool
	{
		$actionName = $event->getParameter('action')->getName();
		$publicActions = ['getInviteSettings'];

		return in_array($actionName, $publicActions, true);
	}
}