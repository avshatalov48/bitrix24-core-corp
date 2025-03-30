<?php

namespace Bitrix\Intranet\ActionFilter;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Main;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class AdminUser extends Engine\ActionFilter\Base
{
	final public function onBeforeAction(Event $event)
	{
		if (!$this->isCurrentUserAdmin())
		{
			$this->addError(new Error(
				Main\Localization\Loc::getMessage('INTRANET_ACTIONFILTER_ALLOWED_ONLY_ADMIN_USER') ?? ''
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	protected function isCurrentUserAdmin(): bool
	{
		return CurrentUser::get()->isAdmin();
	}
}
