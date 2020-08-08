<?php

namespace Bitrix\Intranet\ActionFilter;

use Bitrix\Intranet\Util;
use Bitrix\Main\Context;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class IntranetUser extends Engine\ActionFilter\Base
{
	public const ERROR_ALLOWED_ONLY_INTRANET_USER = 'allowed_only_intranet_user';

	public function onBeforeAction(Event $event)
	{
		if (!Util::isIntranetUser())
		{
			Context::getCurrent()->getResponse()->setStatus(403);
			$this->addError(new Error(
				Loc::getMessage('INTRANET_ACTIONFILTER_ALLOWED_ONLY_INTRANET_USER'),
				self::ERROR_ALLOWED_ONLY_INTRANET_USER
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}