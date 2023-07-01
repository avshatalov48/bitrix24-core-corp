<?php

namespace Bitrix\Crm\Agent\Activity\LightCounter;

use Bitrix\Crm\Activity\Entity\EntityUncompletedActivityTable;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;
use CCrmDateTimeHelper;

/**
 * This is the second part of the agent to populate the LIGHT_TIME fields for counters.
 * Filling the MIN_LIGHT_COUNTER_AT field in b_crm_entity_uncompleted_act table.
 */
class FillLightCounterPart2
{
	public const LAST_ID_OPTION_NAME = 'LIGHT_COUNTER_AGENT_PART_2_LAST_ID';
	public const PART_IS_DONE_NAME = 'LIGHT_COUNTER_AGENT_PART_2_IS_DONE';

	private const BATCH_SIZE = 400;

	private const PART_IS_DONE = false;

	private const PART_HAS_MORE_ITERATIONS = true;

	/**
	 * @return bool return TRUE when no more data to process
	 */
	public function execute(): bool
	{
		if (Option::get('crm', self::PART_IS_DONE_NAME, 'N') === 'Y')
		{
			return self::PART_IS_DONE;
		}

		$lastId = $this->getLastId();
		$rows = $this->queryItemsToProcess($lastId);

		if (empty($rows))
		{
			Option::set('crm', self::PART_IS_DONE_NAME, 'Y');
			return self::PART_IS_DONE;
		}

		$dataToProcess = $this->uniqueRows($rows);

		foreach ($dataToProcess as $item)
		{
			$minLightTime = $this->findMinLightTime($item['ENTITY_TYPE_ID'], $item['ENTITY_ID']);
			$this->updateUncompletedActTable($item['ENTITY_TYPE_ID'], $item['ENTITY_ID'], $minLightTime);
		}

		$this->setLastId(end($rows)['ID']);

		return self::PART_HAS_MORE_ITERATIONS;
	}

	private function uniqueRows(array $rows): array
	{
		$dataToProcess = [];
		foreach ($rows as $row)
		{
			$hash = $row['ENTITY_TYPE_ID'] . '_' . $row['ENTITY_ID'];
			if (!isset($dataToProcess[$hash]))
			{
				$dataToProcess[$hash] = $row;
			}
		}
		return array_values($dataToProcess);
	}

	private function updateUncompletedActTable(int $entityTypeId, int $entityId, DateTime $minLightTime): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$date = $sqlHelper->convertToDbDateTime($minLightTime);

		$sql = <<<SQL
update b_crm_entity_uncompleted_act set MIN_LIGHT_COUNTER_AT = $date
where ENTITY_TYPE_ID = {$entityTypeId} and ENTITY_ID = $entityId;
SQL;
		Application::getConnection()->query($sql);
	}

	private function queryItemsToProcess(int $lastId): array
	{

		return EntityUncompletedActivityTable::query()
			->setSelect(['ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'])
			->whereNull('MIN_LIGHT_COUNTER_AT')
			->where('ID', '>', $lastId)
			->setOrder(['ID'])
			->setLimit(self::BATCH_SIZE)
			->fetchAll();
	}

	private function findMinLightTime(int $entityTypeId, int $entityId): DateTime
	{
		$row = ActCounterLightTimeTable::query()
			->registerRuntimeField('', new ExpressionField('MIN_LIGHT_COUNTER_AT', 'MIN(%s)', 'LIGHT_COUNTER_AT'))
			->addSelect('MIN_LIGHT_COUNTER_AT')
			->registerRuntimeField(
				'',
				new ReferenceField('B',
					ActivityBindingTable::getEntity(),
					['=ref.ACTIVITY_ID' => 'this.ACTIVITY_ID'],
				)
			)
			->where('B.OWNER_ID', '=', $entityId)
			->where('B.OWNER_TYPE_ID', '=', $entityTypeId)
			->fetch();

		return $row['MIN_LIGHT_COUNTER_AT'] ?? CCrmDateTimeHelper::getMaxDatabaseDateObject();
	}

	private function getLastId(): int
	{
		return (int)Option::get('crm', self::LAST_ID_OPTION_NAME, -1);
	}

	private function setLastId(int $lastId)
	{
		Option::set('crm', self::LAST_ID_OPTION_NAME, $lastId);
	}

	public static function cleanOptions()
	{
		Option::delete('crm', ['name' => self::LAST_ID_OPTION_NAME]);
		Option::delete('crm', ['name' => self::PART_IS_DONE_NAME]);
	}
}