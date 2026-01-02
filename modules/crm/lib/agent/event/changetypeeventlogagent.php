<?php

namespace Bitrix\Crm\Agent\Event;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\EventTable;
use Bitrix\Main\Config\Option;

class ChangeTypeEventLogAgent extends AgentBase
{
	private const MODULE = 'crm';
	private const DEFAULT_LIMIT_VALUE = '1000';
	private const OPTION_LIMIT_VALUE = 'change_type_event_table_option_limit_value';

	public static function doRun(): bool
	{
		return (new self())->execute();
	}

	private function execute(): bool
	{
		$eventIds = $this->getEventIds();

		if (empty($eventIds))
		{
			return false;
		}

		foreach ($eventIds as $eventId)
		{
			$this->changeEventType($eventId);
		}

		return true;
	}

	private function getEventIds(): array
	{
		$limit = Option::get(self::MODULE, self::OPTION_LIMIT_VALUE, null) ?? self::DEFAULT_LIMIT_VALUE;

		return EventTable::query()
			->setSelect(['ID'])
			->where('EVENT_TYPE', '=', 0)
			->where('EVENT_ID', '=', 'MESSAGE')
			->where('CREATED_BY_ID', '=', 0)
			->setOrder('ID')
			->setLimit($limit)
			->fetchCollection()
			->getIdList()
		;
	}

	private function changeEventType(int $eventId): void
	{
		EventTable::update($eventId, ['EVENT_TYPE' => 2]);
	}
}
