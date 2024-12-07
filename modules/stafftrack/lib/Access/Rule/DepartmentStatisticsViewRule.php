<?php

namespace Bitrix\StaffTrack\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\StaffTrack\Access\Model\DepartmentStatisticsModel;

class DepartmentStatisticsViewRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof DepartmentStatisticsModel)
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		return in_array($this->user->getUserId(), $item->getUserIds(), true);
	}
}