<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\ActionRule;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;

class RestoreActionRule implements ActionRule
{
	public function canExecute(User $user): bool
	{
		return $user->getInviteStatus() === InvitationStatus::FIRED;
	}
}
