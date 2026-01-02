<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\ActionRule;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;

class DeleteActionRule implements ActionRule
{
	private const ACCEPTED_INVITE_STATUS = [
		InvitationStatus::INVITED,
		InvitationStatus::INVITE_AWAITING_APPROVE,
	];

	public function canExecute(User $user): bool
	{
		return in_array($user->getInviteStatus(), self::ACCEPTED_INVITE_STATUS)
			&& is_null($user->getLastLogin());
	}
}
