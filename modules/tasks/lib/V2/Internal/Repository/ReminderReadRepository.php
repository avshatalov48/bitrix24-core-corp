<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Task\ReminderTable;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Entity\Task\ReminderCollection;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\Reminder\RemindByMapper;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\ReminderMapper;

class ReminderReadRepository implements ReminderReadRepositoryInterface
{
	public function __construct(
		private readonly ReminderMapper $reminderMapper,
	)
	{

	}

	public function getById(int $id): ?Reminder
	{
		$row = ReminderTable::query()
			->setSelect(['ID', 'USER_ID', 'TASK_ID', 'REMIND_DATE', 'TYPE', 'TRANSPORT', 'RECEPIENT_TYPE'])
			->where('ID', $id)
			->exec()
			->fetch();

		if (!is_array($row))
		{
			return null;
		}

		return $this->reminderMapper->mapToEntity($row);
	}

	public function getByTaskId(int $taskId, int $userId, int $offset, int $limit): ReminderCollection
	{
		$rows = ReminderTable::query()
			->setSelect(['ID', 'USER_ID', 'TASK_ID', 'REMIND_DATE', 'TYPE', 'TRANSPORT', 'RECEPIENT_TYPE', 'BEFORE_DEADLINE', 'RRULE'])
			->where('TASK_ID', '=', $taskId)
			->where('USER_ID', '=', $userId)
			->setOffset($offset)
			->setLimit($limit)
			->setOrder(['REMIND_DATE' => 'DESC'])
			->exec()
			->fetchAll();

		return $this->reminderMapper->mapToCollection($rows);
	}

	public function getByDate(DateTime $reminderData, int $limit = 50): ReminderCollection
	{
		$rows = ReminderTable::query()
			->setSelect(['ID', 'USER_ID', 'TASK_ID', 'REMIND_DATE', 'TYPE', 'TRANSPORT', 'RECEPIENT_TYPE', 'BEFORE_DEADLINE', 'RRULE'])
			->where('REMIND_DATE', '<=', $reminderData)
			->setLimit($limit)
			->setOrder(['REMIND_DATE' => 'ASC'])
			->exec()
			->fetchAll();

		return $this->reminderMapper->mapToCollection($rows);
	}

	public function getRecalculableDeadlineReminders(int $taskId): ReminderCollection
	{
		$rows = ReminderTable::query()
			->setSelect(['ID', 'USER_ID', 'TASK_ID', 'REMIND_DATE', 'TYPE', 'TRANSPORT', 'RECEPIENT_TYPE', 'BEFORE_DEADLINE'])
			->where('TASK_ID', '=', $taskId)
			->whereNotNull('BEFORE_DEADLINE')
			->where('TYPE', '=', ReminderTable::TYPE_DEADLINE)
			->exec()
			->fetchAll();

		return $this->reminderMapper->mapToCollection($rows);
	}

	public function getRecurringReminders(int $taskId): ReminderCollection
	{
		$rows = ReminderTable::query()
			->setSelect(['ID', 'USER_ID', 'TASK_ID', 'REMIND_DATE', 'TYPE', 'TRANSPORT', 'RECEPIENT_TYPE', 'RRULE'])
			->where('TASK_ID', '=', $taskId)
			->whereNotNull('RRULE')
			->where('TYPE', '=', ReminderTable::TYPE_RECURRING)
			->exec()
			->fetchAll();

		return $this->reminderMapper->mapToCollection($rows);
	}
}