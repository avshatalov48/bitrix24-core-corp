<?php

namespace Bitrix\BIConnector\Access\Rule;

use Bitrix\BIConnector\Access\Model\DashboardAccessItem;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Superset\SystemDashboardManager;

final class DashboardDeleteRule extends DashboardRule
{
	/**
	 * Check access permission.
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function check(array $params): bool
	{
		$item = $params['item'] ?? null;
		if ($item instanceof DashboardAccessItem)
		{
			if ($item->getType() === SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
			{
				return (
					SystemDashboardManager::canDeleteSystemDashboard()
					&& $this->user->canDeleteRestApp()
				);
			}

			if ($item->getType() === SupersetDashboardTable::DASHBOARD_TYPE_MARKET && !$this->user->canDeleteRestApp())
			{
				return false;
			}

			return parent::check($params);
		}

		return false;
	}

	protected function isAlwaysAvailableForAdmin(): bool
	{
		return SystemDashboardManager::canDeleteSystemDashboard();
	}
}
