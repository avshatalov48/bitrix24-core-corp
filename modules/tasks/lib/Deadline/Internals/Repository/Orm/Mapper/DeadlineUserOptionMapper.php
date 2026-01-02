<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Internals\Repository\Orm\Mapper;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Deadline\Entity\DeadlineUserOption;
use Bitrix\Tasks\Deadline\Internals\Model\DeadlineUserOptionTable;
use Bitrix\Tasks\Deadline\Internals\Model\EO_DeadlineUserOption;
use Bitrix\Tasks\Deadline\SkipNotificationPeriod;

class DeadlineUserOptionMapper
{
	public function convertFromOrm(EO_DeadlineUserOption $eoDeadlineUserOption): DeadlineUserOption
	{
		$skipNotificationPeriod =
			SkipNotificationPeriod::tryFrom($eoDeadlineUserOption->getSkipNotificationPeriod())
			?? SkipNotificationPeriod::DEFAULT
		;

		$deadlineUserOption = new DeadlineUserOption(
			userId: $eoDeadlineUserOption->getUserId(),
			defaultDeadlineInSeconds: $eoDeadlineUserOption->getDefaultDeadline(),
			isExactDeadlineTime: $eoDeadlineUserOption->getIsExactDeadlineTime(),
			skipNotificationPeriod: $skipNotificationPeriod,
			skipNotificationStartDate: $eoDeadlineUserOption->getSkipNotificationStartDate(),
			canChangeDeadline:  $eoDeadlineUserOption->getCanChangeDeadline(),
			maxDeadlineChangeDate: $eoDeadlineUserOption->getMaxDeadlineChangeDate(),
			maxDeadlineChanges: $eoDeadlineUserOption->getMaxDeadlineChanges(),
			requireDeadlineChangeReason: $eoDeadlineUserOption->getRequireDeadlineChangeReason(),
		);

		$deadlineUserOption->id = $eoDeadlineUserOption->getId();

		return $deadlineUserOption;
	}

	/**
				 *
				 *
				 * @throws ArgumentException
				 * @throws SystemException
				 */
				public function convertToOrm(DeadlineUserOption $deadlineUserOption): EO_DeadlineUserOption
	{
		$ormModel =
			$deadlineUserOption->getId()
				? EO_DeadlineUserOption::wakeUp($deadlineUserOption->id)
				: DeadlineUserOptionTable::createObject()
		;

		$ormModel
			->setUserId($deadlineUserOption->userId)
			->setDefaultDeadline($deadlineUserOption->defaultDeadlineInSeconds)
			->setIsExactDeadlineTime($deadlineUserOption->isExactDeadlineTime)
			->setSkipNotificationPeriod($deadlineUserOption->skipNotificationPeriod->value)
			->setSkipNotificationStartDate($deadlineUserOption->skipNotificationStartDate)
			->setCanChangeDeadline($deadlineUserOption->canChangeDeadline)
			->setMaxDeadlineChangeDate($deadlineUserOption->maxDeadlineChangeDate)
			->setMaxDeadlineChanges($deadlineUserOption->maxDeadlineChanges)
			->setRequireDeadlineChangeReason($deadlineUserOption->requireDeadlineChangeReason)
		;

		return $ormModel;
	}
}
