<?php

namespace Bitrix\Intranet\Counters\Synchronizations;

use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\User;
use Bitrix\Intranet\UserTable;
use Bitrix\Main\ArgumentOutOfRangeException;

class WaitConfirmationSynchronization extends AbstractSynchronization
{
	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function sync(Counter $counter): void
	{
		$adminIdList = \CGroup::GetGroupUser(1);
		$totalInvitedUser = (int)UserTable::createInvitedQuery()->where('ACTIVE', 'N')->queryCountTotal();
		foreach ($adminIdList as $id)
		{
			if ((int)$id > 0)
			{
				$counter->setValue(new User((int)$id), $totalInvitedUser);
			}
		}

		$this->next()?->sync($counter);
	}
}