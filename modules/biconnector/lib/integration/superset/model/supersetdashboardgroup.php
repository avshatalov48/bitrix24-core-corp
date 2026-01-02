<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

final class SupersetDashboardGroup extends EO_SupersetDashboardGroup
{
	public function isSystem(): bool
	{
		return $this->getType() === SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM;
	}
}
