<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\BIConnector\Superset\Scope\ScopeService;

class SupersetGroupScope extends EO_SupersetDashboardGroupScope
{
	public function getName(): string
	{
		return ScopeService::getInstance()->getScopeName($this->getScopeCode());
	}
}
