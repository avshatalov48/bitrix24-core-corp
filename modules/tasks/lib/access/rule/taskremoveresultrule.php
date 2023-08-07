<?php

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Tasks\Access\Model\ResultModel;

class TaskRemoveResultRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (!$item instanceof ResultModel)
		{
			return false;
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		if ($item->getCreatedBy() === $this->user->getUserId())
		{
			return true;
		}

		return false;
	}
}