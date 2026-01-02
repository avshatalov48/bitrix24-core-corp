<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\ActionRule;

use Bitrix\Intranet\Entity\User;

class DefaultRule implements ActionRule
{
	public function canExecute(User $user): bool
	{
		return $user->getId() > 0;
	}
}
