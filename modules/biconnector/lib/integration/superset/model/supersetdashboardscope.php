<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\BIConnector\Superset\Scope\ScopeService;

class SupersetDashboardScope extends EO_SupersetScope
{
	public function getName(): string
	{
		return ScopeService::getInstance()->getScopeName($this->getScopeCode());
	}
}
