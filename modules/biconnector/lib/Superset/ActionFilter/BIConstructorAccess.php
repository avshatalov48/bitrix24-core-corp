<?php

namespace Bitrix\BIConnector\Superset\ActionFilter;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class BIConstructorAccess extends Base
{
	public function onBeforeAction(Event $event): ?EventResult
	{
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS))
		{
			$this->addError(new Error(Loc::getMessage('BIC_ACTION_FILTER_ACCESS_DENIED'), 'BIC_ACCESS_DENIED'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
