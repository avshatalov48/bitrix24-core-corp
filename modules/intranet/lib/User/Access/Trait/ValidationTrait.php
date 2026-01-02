<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Access\Trait;

use Bitrix\Intranet\User\Access\Model\TargetUserModel;
use Bitrix\Main\Access\AccessibleItem;

trait ValidationTrait
{
	public function checkModel(AccessibleItem $item): bool
	{
		return $item instanceof TargetUserModel;
	}
}
