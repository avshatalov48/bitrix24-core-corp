<?php

namespace Bitrix\Intranet\Counters\Synchronizations;

use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\User;
use Bitrix\Main\ArgumentOutOfRangeException;

class WaitConfirmationDecrementSynchronization extends AbstractSynchronization
{
	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function sync(Counter $counter): void
	{
		$adminIdList = \CGroup::GetGroupUser(1);
		foreach ($adminIdList as $id)
		{
			if ((int)$id > 0)
			{
				$user = new User($id);
				$currentValue = $counter->getValue($user);
				$counter->setValue($user, $currentValue < 1 ? 0 : $currentValue - 1);
			}
		}

		$this->next()?->sync($counter);
	}
}