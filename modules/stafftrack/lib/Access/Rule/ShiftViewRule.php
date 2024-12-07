<?php

namespace Bitrix\StaffTrack\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\StaffTrack\Access\Model\ShiftModel;

class ShiftViewRule extends AbstractRule
{
	/**
	 * @param AccessibleItem|null $item
	 * @param $params
	 * @return bool
	 */
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof ShiftModel)
		{
			return false;
		}

		return true;
	}
}