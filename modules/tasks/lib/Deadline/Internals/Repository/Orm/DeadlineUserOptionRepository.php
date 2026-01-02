<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Internals\Repository\Orm;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Deadline\Entity\DeadlineUserOption;
use Bitrix\Tasks\Deadline\Internals\Model\DeadlineUserOptionTable;
use Bitrix\Tasks\Deadline\Internals\Repository\DeadlineUserOptionRepositoryInterface;
use Bitrix\Tasks\Deadline\Internals\Repository\Orm\Mapper\DeadlineUserOptionMapper;

class DeadlineUserOptionRepository implements DeadlineUserOptionRepositoryInterface
{
	public function __construct(
		private readonly DeadlineUserOptionMapper $deadlineUserOptionMapper,
	)
	{
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getByUserId(int $userId): DeadlineUserOption
	{
		$ormModel =
			DeadlineUserOptionTable::query()
				->setSelect([
					'ID',
					'USER_ID',
					'DEFAULT_DEADLINE',
					'IS_EXACT_DEADLINE_TIME',
					'SKIP_NOTIFICATION_PERIOD',
					'SKIP_NOTIFICATION_START_DATE',
					'CAN_CHANGE_DEADLINE',
					'MAX_DEADLINE_CHANGE_DATE',
					'MAX_DEADLINE_CHANGES',
					'REQUIRE_DEADLINE_CHANGE_REASON',
				])
				->where('USER_ID', $userId)
				->exec()
				->fetchObject()
		;

		return
			$ormModel
				? $this->deadlineUserOptionMapper->convertFromOrm($ormModel)
				: new DeadlineUserOption($userId)
		;
	}

	public function save(DeadlineUserOption $deadlineUserOption): void
	{
		$insertFields = [
			'USER_ID' => $deadlineUserOption->userId,
			'DEFAULT_DEADLINE' => $deadlineUserOption->defaultDeadlineInSeconds,
			'IS_EXACT_DEADLINE_TIME' => $deadlineUserOption->isExactDeadlineTime,
			'SKIP_NOTIFICATION_PERIOD' => $deadlineUserOption->skipNotificationPeriod->value,
			'SKIP_NOTIFICATION_START_DATE' => $deadlineUserOption->skipNotificationStartDate,
			'CAN_CHANGE_DEADLINE' => $deadlineUserOption->canChangeDeadline,
			'MAX_DEADLINE_CHANGE_DATE' => $deadlineUserOption->maxDeadlineChangeDate,
			'MAX_DEADLINE_CHANGES' => $deadlineUserOption->maxDeadlineChanges,
			'REQUIRE_DEADLINE_CHANGE_REASON' => $deadlineUserOption->requireDeadlineChangeReason,
		];

		$updateFields = [
			'DEFAULT_DEADLINE' => $deadlineUserOption->defaultDeadlineInSeconds,
			'IS_EXACT_DEADLINE_TIME' => $deadlineUserOption->isExactDeadlineTime,
			'SKIP_NOTIFICATION_PERIOD' => $deadlineUserOption->skipNotificationPeriod->value,
			'SKIP_NOTIFICATION_START_DATE' => $deadlineUserOption->skipNotificationStartDate,
			'CAN_CHANGE_DEADLINE' => $deadlineUserOption->canChangeDeadline,
			'MAX_DEADLINE_CHANGE_DATE' => $deadlineUserOption->maxDeadlineChangeDate,
			'MAX_DEADLINE_CHANGES' => $deadlineUserOption->maxDeadlineChanges,
			'REQUIRE_DEADLINE_CHANGE_REASON' => $deadlineUserOption->requireDeadlineChangeReason,
		];

		$uniqueFields = ['USER_ID'];

		DeadlineUserOptionTable::merge($insertFields, $updateFields, $uniqueFields);
	}
}
