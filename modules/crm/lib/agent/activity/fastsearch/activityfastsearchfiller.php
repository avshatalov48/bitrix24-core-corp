<?php

namespace Bitrix\Crm\Agent\Activity\FastSearch;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\Activity\FastSearch\ActivityFastSearchTable;
use Bitrix\Crm\Activity\FastSearch\Sync\ActivitySearchData;
use Bitrix\Crm\Activity\FastSearch\Sync\ActivitySearchDataBuilder;
use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * Fill ActivityFastSearchTable step by step.
 * default batch size is 100. You can change it by option `ActivityFastsearchFiller` in the crm module
 */
final class ActivityFastsearchFiller
{
	private const FILL_THRESHOLD_DAYS = 365;

	private const DONE = false;

	private const CONTINUE = true;

	private const LAST_ID_OPTION_NAME = 'ACTIVITY_FAST_SEARCH_FILLER_LAST_ID';

	private const BATCH_SIZE = 100;

	public function execute(): bool
	{
		$ids = $this->queryActivityIds($this->getLastId());

		if (empty($ids))
		{
			return self::DONE;
		}

		$fastSearchRows = $this->queryActivityData($ids);

		if (empty($fastSearchRows) || $this->isWorkDone(end($fastSearchRows)))
		{
			return self::DONE;
		}

		$this->insertFastSearchRows($fastSearchRows);

		$this->setLastId(end($ids));

		return self::CONTINUE;
	}

	/**
	 * @param ActivitySearchData[] $rows
	 * @return void
	 */
	private function insertFastSearchRows(array $rows): void
	{
		$supportedRows = array_filter($rows, function (ActivitySearchData $row) {
			return  $row->type() !== ActivitySearchData::TYPE_UNSUPPORTED;
		});

		if (empty($supportedRows))
		{
			return;
		}

		$entity = ActivityFastSearchTable::getEntity();
		$connection = $entity->getConnection();
		$helper = $connection->getSqlHelper();

		if (!method_exists($helper, 'getInsertIgnore'))
		{
			return;
		}

		$fields = null;
		$values = [];
		foreach ($supportedRows as $row)
		{
			$ins = $helper->prepareInsert(ActivityFastSearchTable::getTableName(), $row->toORMArray());
			if (!isset($fields))
			{
				$fields = $ins[0];
			}
			$values[] = "($ins[1])";
		}

		$valuesStr = implode(',', $values);
		$sql = $helper->getInsertIgnore(
			ActivityFastSearchTable::getTableName(),
			"($fields)",
			" VALUES $valuesStr"
		);

		$connection->queryExecute($sql);
	}

	/**
	 * @param array $ids
	 * @return ActivitySearchData[]
	 */
	private function queryActivityData(array $ids): array
	{
		$builder = new ActivitySearchDataBuilder();
		$act = ActivityTable::query()
			->addSelect('ID')
			->addSelect('CREATED')
			->addSelect('DEADLINE')
			->addSelect('RESPONSIBLE_ID')
			->addSelect('AUTHOR_ID')
			->addSelect('COMPLETED')
			->addSelect('PROVIDER_ID')
			->addSelect('PROVIDER_TYPE_ID')
			->addSelect('TYPE_ID')
			->addSelect('DIRECTION')
			->addSelect('REF_INC.ID', 'INC_ID')
			->registerRuntimeField('',
				new ReferenceField('REF_INC',
					IncomingChannelTable::getEntity(),
					['=this.ID' => 'ref.ACTIVITY_ID'],
					['join_type' => Join::TYPE_LEFT]
				)
			)
			->whereIn('ID', $ids);

		return array_map(function ($row) use ($builder) {
			$row['IS_INCOMING_CHANNEL'] = empty($row['INC_ID']) ? 'N' : 'Y';
			return $builder->build($row);
		}, $act->fetchAll());
	}

	/**
	 * @param int $lastId
	 * @return int[]
	 */
	private function queryActivityIds(int $lastId): array
	{
		$q = ActivityTable::query()
			->setSelect(['ID'])
			->where('ID', '<', $lastId)
			->setOrder(['ID' => 'DESC'])
			->setLimit($this->getLimit());

		return array_column($q->fetchAll(), 'ID');
	}

	private function getLastId(): int
	{
		$maxInt = PHP_INT_MAX;
		return (int)Option::get('crm', self::LAST_ID_OPTION_NAME, $maxInt);
	}

	private function setLastId(int $id): void
	{
		Option::set('crm', self::LAST_ID_OPTION_NAME, $id);
	}

	private function isWorkDone(ActivitySearchData $lastRow): bool
	{
		// add 1 extra day to compensate for possible problems from using the PRESERVE_CREATION_TIME option
		$days = self::FILL_THRESHOLD_DAYS + 1;
		$lastDate = (new DateTime())->add('-P'.$days.'D');
		return $lastRow->created()->getTimestamp() < $lastDate->getTimestamp();
	}

	private function getLimit(): int
	{
		return (int)Option::get('crm', 'ActivityFastsearchFiller', self::BATCH_SIZE);
	}

	public static function cleanOptions(): void
	{
		Option::delete('crm', ['name' => self::LAST_ID_OPTION_NAME]);
	}

	public static function isRunning(): bool
	{
		return Option::get('crm', self::LAST_ID_OPTION_NAME, null) !== null;
	}
}