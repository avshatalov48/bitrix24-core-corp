<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\ActionRule;

use Bitrix\Intranet\Entity\User;

interface ActionRule
{
	public function canExecute(User $user): bool;
}
