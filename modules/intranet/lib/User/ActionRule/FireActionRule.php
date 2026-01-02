<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\ActionRule;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;

class FireActionRule implements ActionRule
{
	public function canExecute(User $user): bool
	{
		return $user->getInviteStatus() === InvitationStatus::ACTIVE;
	}
}
