<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Tasks\Internals\Task\ReminderTable;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Entity\Task\ReminderCollection;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\ReminderMapper;

class ReminderRepository implements ReminderRepositoryInterface
{
	public function __construct(
		private readonly ReminderMapper $reminderMapper,
	)
	{

	}

	public function save(Reminder $reminder): int
	{
		if ($reminder->getId() > 0)
		{
			return $this->update($reminder);
		}

		return $this->add($reminder);
	}

	public function saveBatch(ReminderCollection $reminders): void
	{
		$data = $this->reminderMapper->mapFromCollection($reminders);
		if (empty($data))
		{
			return;
		}

		$result = ReminderTable::addMergeMulti($data);
		if (!$result->isSuccess())
		{
			throw new SqlQueryException($result->getError()?->getMessage());
		}
	}

	public function deleteByFilter(array $filter): void
	{
		ReminderTable::deleteByFilter($filter);
	}

	private function add(Reminder $reminder): int
	{
		$data = $this->reminderMapper->mapFromEntity($reminder);

		$result = ReminderTable::add($data);
		if (!$result->isSuccess())
		{
			throw new SqlQueryException($result->getError()?->getMessage());
		}

		return $result->getId();
	}

	private function update(Reminder $reminder): int
	{
		$data = $this->reminderMapper->mapFromEntity($reminder);
		unset($data['ID']);

		$result = ReminderTable::update($reminder->getId(), $data);
		if (!$result->isSuccess())
		{
			throw new SqlQueryException($result->getError()?->getMessage());
		}

		return $result->getId();
	}
}