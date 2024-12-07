<?php

namespace Bitrix\BIConnector\Access\Rule;

use Bitrix\BIConnector\Access\Model\DashboardAccessItem;

class DashboardRule extends VariableRule
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
			if ($this->isAbleToSkipChecking() || $this->user->getUserId() === $item->getOwnerId())
			{
				return true;
			}

			return parent::check($params);
		}

		return false;
	}
}
