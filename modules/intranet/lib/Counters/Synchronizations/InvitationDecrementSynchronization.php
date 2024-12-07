<?php

namespace Bitrix\Intranet\Counters\Synchronizations;

use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\User;

class InvitationDecrementSynchronization extends AbstractSynchronization
{
	public function __construct(
		private User $inviteeUser
	)
	{
	}

	public function sync(Counter $counter): void
	{
		if (!$this->inviteeUser->isAdmin())
		{
			$currentValue = $this->inviteeUser->numberOfInvitationsSent();
			$counter->setValue($this->inviteeUser, $currentValue < 1 ? 0 : $currentValue - 1);
		}

		$this->next()?->sync($counter);
	}
}