<?php

namespace Bitrix\Intranet\Infrastructure\Controller\ActionFilter;

use Bitrix\Main;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Intranet;

class InviteIntranetAccessControl extends Engine\ActionFilter\Base
{
	public function onBeforeAction(Event $event)
	{
		Main\Localization\Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intranet/lib/actionfilter/inviteintranetaccesscontrol.php');
		if (!Intranet\Invitation::canCurrentUserInvite())
		{
			$this->addError(new Error(
				Main\Localization\Loc::getMessage('INTRANET_INVITE_ACCESS_CONTROL_ACCESS_DENIED')
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
