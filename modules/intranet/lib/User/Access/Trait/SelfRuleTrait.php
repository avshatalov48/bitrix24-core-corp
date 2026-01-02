<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Access\Trait;

use Bitrix\Main\Access\AccessibleItem;

trait SelfRuleTrait
{
	public function isSelfAction(AccessibleItem $item): bool
	{
		return $item->getId() === $this->user?->getUserId();
	}
}
