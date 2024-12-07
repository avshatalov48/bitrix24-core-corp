<?php

namespace Bitrix\Intranet\Counters\Synchronizations;

use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\User;

class WaitConfirmationResetSynchronization extends AbstractSynchronization
{
	public function __construct(
		private ?User $inviteeUser
	)
	{
	}

	public function sync(Counter $counter): void
	{
		if ($this->inviteeUser && !$this->inviteeUser->isAdmin())
		{
			$counter->setValue(
				$this->inviteeUser,
				0
			);
		}
	}
}