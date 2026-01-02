<?php

namespace Bitrix\BIConnector\Superset\ActionFilter;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class BIConstructorAccess extends Base
{
	public function onBeforeAction(Event $event): ?EventResult
	{
		if (Loader::includeModule('intranet') && !ToolsManager::getInstance()->checkAvailabilityByToolId('crm_bi'))
		{
			$this->addError(new Error(Loc::getMessage('BIC_ACTION_FILTER_TOOL_DISABLED')));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS))
		{
			$this->addError(new Error(Loc::getMessage('BIC_ACTION_FILTER_ACCESS_DENIED')));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
