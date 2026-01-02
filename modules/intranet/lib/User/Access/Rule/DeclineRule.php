<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Access\Rule;

use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;

class DeclineRule extends AbstractRule
{
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		return $this->controller->check(
			UserActionDictionary::CONFIRM,
			$item,
			$params,
		);
	}
}
