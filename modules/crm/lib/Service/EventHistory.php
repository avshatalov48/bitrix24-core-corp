<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\EO_Event;
use Bitrix\Crm\EO_EventRelations;
use Bitrix\Crm\EventRelationsTable;
use Bitrix\Crm\EventTable;
use Bitrix\Crm\Service\EventHistory\EventHistoryData;
use Bitrix\Crm\Service\EventHistory\TrackedObject;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class EventHistory
{
	protected const EVENT_TYPE_USER = 0;
	protected const EVENT_TYPE_UPDATE = 1;
	protected const EVENT_TYPE_EMAIL = 2;
	protected const EVENT_TYPE_VIEW = 3;
	protected const EVENT_TYPE_EXPORT = 4;
	protected const EVENT_TYPE_DELETE = 5;

	/**
	 * Creates a new 'view' record in the EventsTable for the provided object
	 *
	 * @param TrackedObject $trackedObject
	 *
	 * @return Result
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function registerView(TrackedObject $trackedObject): Result
	{
		$subQuery = EventRelationsTable::query()
			->addSelect('EVENT_ID')
			->addFilter('=ENTITY_TYPE', $trackedObject->getEntityType())
			->addFilter('=ENTITY_ID', $trackedObject->getEntityId());

		$interval = HistorySettings::getCurrent()->getViewEventGroupingInterval();
		$time = new DateTime();
		$time->add("-T{$interval}M");

		$query = EventTable::query()
			->addSelect('DATE_CREATE')
			->addFilter('=EVENT_TYPE', static::EVENT_TYPE_VIEW)
			->addFilter('>=DATE_CREATE', $time)
			->addFilter('@ID', new SqlExpression($subQuery->getQuery()))
			->addOrder('DATE_CREATE', 'DESC')
			->setLimit(1);

		// Don't create a new view record if another one was created recently
		if(is_array($query->exec()->fetch()))
		{
			return new Result();
		}

		$eventData = $trackedObject->prepareViewEventData();

		return $this->add(static::EVENT_TYPE_VIEW, $eventData);
	}

	/**
	 * Creates a new 'delete' record in the EventsTable for the provided object
	 *
	 * @param TrackedObject $trackedObject
	 *
	 * @return Result
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function registerDelete(TrackedObject $trackedObject): Result
	{
		$eventData = $trackedObject->prepareDeleteEventData();

		return $this->add(static::EVENT_TYPE_DELETE, $eventData);
	}

	/**
	 * Creates a new 'update' record in the EventsTable. The objects are compared and found differences are registered.
	 *
	 * @param TrackedObject $trackedObject
	 *
	 * @return Result
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function registerUpdate(TrackedObject $trackedObject): Result
	{
		$eventData = $trackedObject->prepareUpdateEventData();

		return $this->add(static::EVENT_TYPE_UPDATE, $eventData);
	}

	/**
	 * Adds new event record in the DB
	 *
	 * @param int $eventType
	 * @param EventHistoryData|EventHistoryData[] $eventData
	 *
	 * @return Result
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function add(int $eventType, $eventData): Result
	{
		$result = new Result();

		if (is_array($eventData))
		{
			foreach ($eventData as $eventDataNested)
			{
				$localResult = $this->add($eventType, $eventDataNested);
				if (!$localResult->isSuccess())
				{
					$result->addErrors($localResult->getErrors());
				}
			}

			return $result;
		}

		$eventDataArray = $eventData->toArray();

		$this->sendEvent('OnBeforeCrmAddEvent', $eventDataArray);

		$eventTableRecord = $this->createEventTableRecord($eventType);
		/** @noinspection PhpParamsInspection */
		$eventTableResult = $this->saveRecord($eventTableRecord, $eventDataArray);
		if (!empty($eventTableResult->getErrors()))
		{
			$result->addErrors($eventTableResult->getErrors());
			return $result;
		}

		$eventRelationsTableRecord = $this->createEventRelationsTableRecord($eventTableResult->getId());
		/** @noinspection PhpParamsInspection */
		$eventRelationsTableResult = $this->saveRecord($eventRelationsTableRecord, $eventDataArray);
		if (!empty($eventRelationsTableResult->getErrors()))
		{
			$result->addErrors($eventRelationsTableResult->getErrors());
		}

		if ($result->isSuccess())
		{
			$this->sendEvent('OnAfterCrmAddEvent', [$eventTableResult->getId(), $eventDataArray]);
		}

		return $result;
	}

	protected function sendEvent(string $eventName, array $eventData): void
	{
		$event = new Event('crm', $eventName, $eventData);
		EventManager::getInstance()->send($event);
	}

	/**
	 * Creates an EO_Event object with default values set
	 * @param int $eventType
	 *
	 * @return EO_Event
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function createEventTableRecord(int $eventType): EO_Event
	{
		// Empty strings by default because old API (CCrmEvent) was using them instead of NULL value
		return EventTable::createObject(
			[
				'CREATED_BY_ID' => Container::getInstance()->getContext()->getUserId(),
				'EVENT_ID' => '',
				'EVENT_NAME' => $this->getDefaultEventName($eventType),
				'EVENT_TYPE' => $eventType,
				'EVENT_TEXT_1' => '',
				'EVENT_TEXT_2' => '',
				'DATE_CREATE' => new DateTime(),
				'FILES' => null,
			]
		);
	}

	protected function getDefaultEventName(int $eventType): string
	{
		$map = [
			static::EVENT_TYPE_USER => Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_USER'),
			static::EVENT_TYPE_UPDATE => Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_UPDATE'),
			static::EVENT_TYPE_EMAIL => Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_EMAIL'),
			static::EVENT_TYPE_VIEW => Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_VIEW'),
			static::EVENT_TYPE_EXPORT => Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_EXPORT'),
			static::EVENT_TYPE_DELETE => Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_DELETE'),
		];

		return $map[$eventType] ?? '';
	}

	/**
	 * Creates an EO_EventRelations object with default values set
	 * @param int $eventId
	 *
	 * @return EO_EventRelations
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function createEventRelationsTableRecord(int $eventId): EO_EventRelations
	{
		// Empty strings by default because old API (CCrmEvent) was using them instead of NULL value
		return EventRelationsTable::createObject(
			[
				// EVENT_ID in EventTable and EVENT_ID EventRelationsTable are absolutely different fields
				'EVENT_ID' => $eventId,
				'ENTITY_TYPE' => '',
				'ENTITY_ID' => 0,
				'ENTITY_FIELD' => '',
				'ASSIGNED_BY_ID' => Container::getInstance()->getContext()->getUserId(),
			]
		);
	}

	protected function saveRecord(EntityObject $record, array $eventDataArray): AddResult
	{
		foreach ($record->sysGetEntity()->getScalarFields() as $field)
		{
			$fieldName = $field->getName();
			if (isset($eventDataArray[$fieldName]) && $fieldName !== 'EVENT_ID')
			{
				$record->set($fieldName, $eventDataArray[$fieldName]);
			}
		}

		return $record->save();
	}
}
