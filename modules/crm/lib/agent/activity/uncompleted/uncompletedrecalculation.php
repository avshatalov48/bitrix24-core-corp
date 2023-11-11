<?php

namespace Bitrix\Crm\Agent\Activity\Uncompleted;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Activity\UncompletedActivity;
use Bitrix\Main\Config\Option;

/**
 * Recalculate all rows in entity_uncompleted_activity table to fix any possible problems
 * It may be called if table has corrupt data and can't be fixed any other way.
 *
 * Default batch size is 10 you may change it by using option 'ACTIVITY_UNCOMPLETED_RECALCULATION_BATCH_SIZE'.
 * `\Bitrix\Main\Config\Option::set('crm', 'ACTIVITY_UNCOMPLETED_RECALCULATION_BATCH_SIZE', 50);`
 *
 */
class UncompletedRecalculation
{
	private const DONE = false;

	private const CONTINUE = true;

	private const LAST_ID_OPTION_NAME = 'ACTIVITY_UNCOMPLETED_RECALCULATION_LAST_ID';

	private const CUSTOM_BATCH_SIZE_OPTION_NAME = 'ACTIVITY_UNCOMPLETED_RECALCULATION_BATCH_SIZE';

	private const DEFAULT_BATCH_SIZE = 10;

	public function execute(): bool
	{
		$ids = $this->queryIds($this->getLastId());

		if (empty($ids))
		{
			return self::DONE;
		}

		$rows = $this->getUncompletedRows($ids);

		foreach ($rows as $row)
		{
			UncompletedActivity::synchronizeForActivity((int)$row['ACTIVITY_ID']);
		}

		$newLastId = end($rows)['ID'] ?? $this->getLastId();
		$this->setLastId($newLastId);

		return self::CONTINUE;
	}


	private function queryIds(int $lastId): array
	{
		$q = EntityUncompletedActivityTable::query()
			->setSelect(['ID'])
			->where('ID', '>', $lastId)
			->where('RESPONSIBLE_ID', '>', 0) // row with RESPONSIBLE_ID = 0 will be recalculated together with `activity` user
			->setOrder(['ID' => 'ASC'])
			->setLimit($this->getLimit());

		return array_column($q->fetchAll(), 'ID');
	}

	private function getUncompletedRows(array $ids): array
	{
		return EntityUncompletedActivityTable::query()
			->setSelect([
				'ID',
				'ACTIVITY_ID',
			])
			->whereIn('ID', $ids)
			->fetchAll();
	}

	private function getLastId(): int
	{
		return (int)Option::get('crm', self::LAST_ID_OPTION_NAME, -1);
	}

	private function setLastId(int $id): void
	{
		Option::set('crm', self::LAST_ID_OPTION_NAME, $id);
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', self::CUSTOM_BATCH_SIZE_OPTION_NAME, self::DEFAULT_BATCH_SIZE);
	}

	public static function cleanOptions(): void
	{
		Option::delete('crm', ['name' => self::LAST_ID_OPTION_NAME]);
	}
}