<?php

namespace Bitrix\Booking\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;

class ResourceDeleteRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		return true;
	}
}
