<?php

namespace Bitrix\Crm\Agent\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;
use CCrmOwnerType;

class SyncLogMessageSourceIdAgent extends Stepper
{
	private const BATCH_LIMIT = 50;

	protected static $moduleId = 'crm';

	public function execute(array &$option)
	{
		if (!Loader::includeModule(self::$moduleId))
		{
			return false;
		}

		$option['steps'] = (int)($option['steps'] ?? 0);
		$lastId = ($option['lastId'] ?? 0);
		$processedCount = 0;

		$items = $this->getList($lastId);
		foreach ($items as $item)
		{
			$lastId = (int)$item['ID'];
			$option['steps']++;
			$processedCount++;

			if (empty($item['LOG_MESSAGE_SOURCE_ID']))
			{
				continue;
			}

			TimelineTable::update((int)$item['ID'], [
				'SOURCE_ID' => $item['LOG_MESSAGE_SOURCE_ID']
			]);
		}

		$option['lastId'] = $lastId;

		return ($processedCount < self::BATCH_LIMIT) ? self::FINISH_EXECUTION : self::CONTINUE_EXECUTION;
	}

	private function getList(int $lastId): array
	{
		$ids = array_column(
			TimelineTable::query()
				->setSelect(['ID'])
				->where('ID', '>', $lastId)
				->where('TYPE_ID', TimelineType::LOG_MESSAGE)
				->whereIn('TYPE_CATEGORY_ID', [LogMessageType::CALL_INCOMING, LogMessageType::OPEN_LINE_INCOMING])
				->whereNot('ASSOCIATED_ENTITY_TYPE_ID', CCrmOwnerType::Activity)
				->setLimit(self::BATCH_LIMIT)
				->setOrder(['ID' => 'ASC'])
				->fetchAll(),
			'ID'
		);
		if (empty($ids))
		{
			return [];
		}

		$items = TimelineTable::query()
			->setSelect(['ID', 'TYPE_CATEGORY_ID', 'SETTINGS'])
			->whereIn('ID', $ids)
			->fetchAll()
		;

		foreach ($items as &$item)
		{
			$key = $item['TYPE_CATEGORY_ID'] == LogMessageType::CALL_INCOMING ? 'SOURCE_ID' : 'SOURCE';
			$item['LOG_MESSAGE_SOURCE_ID'] = $item['SETTINGS']['BASE'][$key] ?? '';

			unset($item['SETTINGS'], $item['TYPE_CATEGORY_ID']);
		}
		unset($item);

		return $items;
	}
}
