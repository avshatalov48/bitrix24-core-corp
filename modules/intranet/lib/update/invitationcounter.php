<?php

namespace Bitrix\Intranet\Update;

use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\User;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\UserTable;

class InvitationCounter extends Stepper
{
	protected static $moduleId = "intranet";
	private int $limit = 100;
	private ?int $totalWaitingInvitation = null;

	public function getUserIds($offset = 0): array
	{
		 return UserTable::query()
			->setLimit($this->limit)
			->setSelect(['ID'])
			->setOffset($offset)
			->fetchCollection()
			->getIdList();
	}

	public function execute(array &$result): bool
	{
		if (empty($result))
		{
			$result["steps"] = 0;
			$result["count"] = ceil(UserTable::getCount() / $this->limit);
		}
		$userIds = $this->getUserIds($result["steps"] * $this->limit);
		foreach ($userIds as $id)
		{
			$user = new User($id);
			$invitationCounter = new Counter(
				Invitation::getInvitedCounterId()
			);

			$invitationCounterValue = $user->numberOfInvitationsSent();
			$invitationCounter->setValue($user, $invitationCounterValue);
			$waitingCounterValue = 0;
			if ($user->isAdmin())
			{
				if (!$this->totalWaitingInvitation)
				{
					$this->totalWaitingInvitation = (int)\Bitrix\Intranet\UserTable::createInvitedQuery()
						->where('ACTIVE', 'N')->queryCountTotal();
				}
				$waitingCounter = new Counter(Invitation::getWaitConfirmationCounterId());
				$waitingCounter->setValue($user, $this->totalWaitingInvitation);
				$waitingCounterValue = $this->totalWaitingInvitation;
			}

			$total = $waitingCounterValue + $invitationCounterValue;
			$totalCounter = new Counter(Invitation::getTotalInvitationCounterId());
			$totalCounter->setValue($user, $total);
		}
		$result["steps"]++;

		return ($result["steps"] <= $result["count"] ? self::CONTINUE_EXECUTION : self::FINISH_EXECUTION);
	}

}