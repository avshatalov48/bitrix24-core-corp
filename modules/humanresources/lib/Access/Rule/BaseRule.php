<?php

namespace Bitrix\HumanResources\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;

class BaseRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		//TODO: Access rules
	}
}