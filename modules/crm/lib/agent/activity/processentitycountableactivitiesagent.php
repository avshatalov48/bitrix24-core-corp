<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

class ProcessEntityCountableActivitiesAgent extends Stepper
{
	protected static $moduleId = 'crm';

	public function execute(array &$result)
	{
		$result['steps'] = (int)($result['steps'] ?? 0);

		$limit = $this->getLimit();
		$lastId = ($result['lastId'] ?? 0);
		$processedCount = 0;

		$items = $this->getList($lastId, $limit);

		foreach ($items as $item)
		{
			$lastId = (int)$item['ID'];
			$result['steps']++;
			$processedCount++;

			if (!\CCrmDateTimeHelper::IsMaxDatabaseDate($item['DEADLINE']) || $item['IS_INCOMING_CHANNEL'] == 'Y')
			{
				// synchronize activities with deadline or incoming:
				\Bitrix\Crm\Counter\Monitor\CountableActivitySynchronizer::initialSynchronizeByActivityId($lastId);
			}
		}

		$result['lastId'] = $lastId;

		if ($processedCount < $limit)
		{
			$this->onStepperComplete();

			return self::FINISH_EXECUTION;
		}
		else
		{
			return self::CONTINUE_EXECUTION;
		}
	}

	protected function getLimit(): int
	{
		return (int)Option::get('crm', 'EntityCountableActAgentLimit', 50);
	}

	protected function onStepperComplete(): void
	{
		\COption::RemoveOption('crm', 'enable_entity_countable_act');
		\COption::RemoveOption('crm', 'is_counters_enabled');
	}

	protected function getList(int $lastId, int $limit): array
	{
		$ids = array_column(ActivityTable::query()
			->setSelect([
				'ID',
			])
			->where('ID', '>', $lastId)
			->where('COMPLETED', false)
			->setLimit($limit)
			->setOrder(['ID' => 'ASC'])
			->fetchAll(), 'ID')
		;
		if (empty($ids))
		{
			return [];
		}

		$items = ActivityTable::query()
			->setSelect([
				'ID',
				'DEADLINE',
			])
			->whereIn('ID', $ids)
			->fetchAll()
		;
		$incomingChannelActivityIds = array_column(
			IncomingChannelTable::query()
				->setSelect([
					'ACTIVITY_ID',
				])
				->whereIn('ACTIVITY_ID', $ids)
				->fetchAll(),
		'ACTIVITY_ID'
		);
		foreach ($items as &$item)
		{
			$item['IS_INCOMING_CHANNEL'] = in_array($item['ID'], $incomingChannelActivityIds, false) ? 'Y' : 'N';
		}

		return $items;
	}
}
