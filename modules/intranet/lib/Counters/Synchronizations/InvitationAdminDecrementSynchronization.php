<?php

namespace Bitrix\Intranet\Counters\Synchronizations;

use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\User;
use Bitrix\Intranet\UserTable;

class InvitationAdminDecrementSynchronization extends AbstractSynchronization
{
	public function sync(Counter $counter): void
	{
		$adminIdList = \CGroup::GetGroupUser(1);
		$totalInvitedUser = (int)UserTable::createInvitedQuery()->where('ACTIVE', 'Y')->queryCountTotal();
		foreach ($adminIdList as $id)
		{
			if ((int)$id > 0)
			{
				$user = new User((int)$id);
				$counter->setValue($user, $totalInvitedUser < 1 ? 0 : $totalInvitedUser - 1);
			}
		}

		$this->next()?->sync($counter);
	}
}