<?php

namespace Bitrix\Crm\Agent\Event;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\EventRelationsTable;
use Bitrix\Crm\EventTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use CFile;

class CleanOldEventLogAgent extends AgentBase
{
	private const MODULE = 'crm';
	private const DEFAULT_LIMIT_VALUE = 1000;
	private const DEFAULT_CLEAN_INTERVAL = 'P1Y';
	private const ONE_DAY_IN_SECONDS = 86400;
	private const OPTION_LIMIT_VALUE = 'clean_old_event_table_option_limit_value';
	private const OPTION_CLEAN_INTERVAL = 'clean_old_event_table_option_clean_interval';
	private array $events = [];
	private array $eventTypes = [
		1, 2, 3, 4, 5, 6, 7, 8,
	];

	public static function doRun(): bool
	{
		$instance = new self();

		$instance->execute();

		return true;
	}

	private function execute(): void
	{
		$eventIds = $this->getEventIds();

		if (empty($eventIds))
		{
			$this->forceNextAgentExecution(self::ONE_DAY_IN_SECONDS);

			return;
		}

		$this->fillEventDataByIds($eventIds);

		foreach ($this->events as $event)
		{
			$this->cleanRelatedFiles($event);

			$this->cleanRelations($event['ID']);

			$this->deleteEvent($event['ID']);
		}

		$this->forceNextAgentExecution();
	}

	private function getEventIds(): array
	{
		$limit = Option::get(self::MODULE, self::OPTION_LIMIT_VALUE, null) ?? self::DEFAULT_LIMIT_VALUE;

		return EventTable::query()
			->setSelect(['ID'])
			->whereIN('EVENT_TYPE', $this->eventTypes)
			->where('DATE_CREATE', '<', $this->getMinimalCleanInterval())
			->setLimit($limit)
			->fetchCollection()
			->getIdList();
	}

	private function fillEventDataByIds(array $eventIds): void
	{
		$this->events = EventTable::query()
			->setSelect(['ID', 'FILES'])
			->whereIn('ID', $eventIds)
			->fetchAll();
	}

	private function cleanRelatedFiles(array $event): void
	{
		$fileIds = unserialize($event['FILES'], ['allowed_classes' => false]);

		if (!$fileIds)
		{
			return;
		}

		foreach ($fileIds as $fileId)
		{
			CFile::Delete((int) $fileId);
		}
	}

	private function cleanRelations(int $eventId): void
	{
		$filter = [
			'=EVENT_ID' => $eventId,
		];

		$entity = EventRelationsTable::getEntity();
		$connection = $entity->getConnection();

		$connection->query(sprintf(
			'DELETE FROM %s WHERE %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			Query::buildFilterSql($entity, $filter)
		));
	}

	private function deleteEvent(int $eventId): void
	{
		EventTable::delete($eventId);
	}

	private function getCleanInterval(): string
	{
		return Option::get(self::MODULE, self::OPTION_CLEAN_INTERVAL, null) ?? self::DEFAULT_CLEAN_INTERVAL;
	}

	private function getMinimalCleanInterval(): DateTime
	{
		return (new DateTime())->add('-'.$this->getCleanInterval()); //subtracts clean interval
	}

	private function forceNextAgentExecution(int $periodInSeconds = 60): void
	{
		global $pPERIOD;

		$pPERIOD = $periodInSeconds; // some magic to run the agent next time in $periodInSeconds seconds
	}
}