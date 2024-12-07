<?php

namespace Bitrix\Intranet\Counters\Synchronizations;

use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\User;

class InvitationSynchronization extends AbstractSynchronization
{
	public function __construct(
		private User $inviteeUser
	)
	{
	}

	public function sync(Counter $counter): void
	{
		$counter->setValue($this->inviteeUser, $this->inviteeUser->numberOfInvitationsSent());

		$this->next()?->sync($counter);
	}
}