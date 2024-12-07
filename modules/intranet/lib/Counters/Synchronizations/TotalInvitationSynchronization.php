<?php

namespace Bitrix\Intranet\Counters\Synchronizations;

use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\User;
use Bitrix\Main\ArgumentOutOfRangeException;

class TotalInvitationSynchronization extends AbstractSynchronization
{
	public function __construct(
		private ?User $inviteeUser = null
	)
	{
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function sync(Counter $counter): void
	{

		$invitationCounter = new Counter(Invitation::getInvitedCounterId());
		$waitCounter = new Counter(Invitation::getWaitConfirmationCounterId());

		if ($this->inviteeUser && !$this->inviteeUser->isAdmin())
		{
			$counter->setValue(
				$this->inviteeUser,
				$invitationCounter->getValue($this->inviteeUser) + $waitCounter->getValue($this->inviteeUser)
			);
		}


		$adminIdList = \CGroup::GetGroupUser(1);
		foreach ($adminIdList as $id)
		{
			if ((int)$id > 0)
			{
				$user = new User((int)$id);
				$counter->setValue(
					$user,
					$invitationCounter->getValue($user) + $waitCounter->getValue($user)
				);
			}
		}

		$this->next()?->sync($counter);
	}
}