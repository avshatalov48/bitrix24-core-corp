<?php

namespace Bitrix\BIConnector\Superset\ActionFilter;

use Bitrix\BIConnector;
use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class WorkspaceAnalyticAccess extends Base
{
	public function onBeforeAction(Event $event): ?EventResult
	{
		if (!BIConnector\Configuration\Feature::isExternalEntitiesEnabled())
		{
			$this->addError(new Error(Loc::getMessage('BI_ACTION_FILTER_WORKSPACE_FEATURE')));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG))
		{
			$this->addError(new Error(Loc::getMessage('BI_ACTION_FILTER_WORKSPACE_ACCESS')));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
