<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\EO_Event;
use Bitrix\Crm\EO_EventRelations;
use Bitrix\Crm\EventRelationsTable;
use Bitrix\Crm\EventTable;
use Bitrix\Crm\Service\EventHistory\EventHistoryData;
use Bitrix\Crm\Service\EventHistory\TrackedObject;
use Bitrix\Crm\Settings\HistorySettings;
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
	public const EVENT_TYPE_USER = 0;
	public const EVENT_TYPE_UPDATE = 1;
	public const EVENT_TYPE_EMAIL = 2;
	public const EVENT_TYPE_VIEW = 3;
	public const EVENT_TYPE_EXPORT = 4;
	public const EVENT_TYPE_DELETE = 5;
	public const EVENT_TYPE_MERGER = 6;
	public const EVENT_TYPE_LINK = 7;
	public const EVENT_TYPE_UNLINK = 8;

	/** @var Array<int, string> */
	private array $eventTypeCaptions;

	public function __construct()
	{
		$this->eventTypeCaptions = [
			static::EVENT_TYPE_USER => (string)Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_USER'),
			static::EVENT_TYPE_UPDATE => (string)Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_UPDATE'),
			static::EVENT_TYPE_EMAIL => (string)Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_EMAIL'),
			static::EVENT_TYPE_VIEW => (string)Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_VIEW'),
			static::EVENT_TYPE_EXPORT => (string)Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_EXPORT'),
			static::EVENT_TYPE_DELETE => (string)Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_DELETE'),
			static::EVENT_TYPE_LINK => (string)Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_LINK'),
			static::EVENT_TYPE_UNLINK => (string)Loc::getMessage('CRM_EVENT_HISTORY_EVENT_NAME_UNLINK'),
		];
	}

	/**
	 * @return Array<int, string>
	 */
	public function getAllEventTypeCaptions(): array
	{
		return $this->eventTypeCaptions;
	}

	public function getEventTypeCaption(int $eventType): string
	{
		return $this->eventTypeCaptions[$eventType] ?? '';
	}

	/**
	 * Creates a new 'view' record in the EventsTable for the provided object
	 *
	 * @param TrackedObject $trackedObject
	 * @param Context|null $context
	 *
	 * @return Result
	 */
	public function registerView(TrackedObject $trackedObject, Context $context = null): Result
	{
		$context = $context ?? Container::getInstance()->getContext();
		$userId = $context->getUserId();
		if (!$userId)
		{
			return new Result();
		}
		if (!Container::getInstance()->getUserBroker()->isRealUser($userId))
		{
			return new Result();
		}

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
			->addFilter('=CREATED_BY_ID', $userId)
			->addFilter('@ID', new SqlExpression($subQuery->getQuery()))
			->addOrder('DATE_CREATE', 'DESC')
			->setLimit(1);

		// Don't create a new view record if another one was created recently
		if(is_array($query->exec()->fetch()))
		{
			return new Result();
		}

		$eventData = $trackedObject->prepareViewEventData();

		return $this->add(static::EVENT_TYPE_VIEW, $eventData, $context);
	}

	/**
	 * Creates a new 'delete' record in the EventsTable for the provided object
	 *
	 * @param TrackedObject $trackedObject
	 * @param Context|null $context
	 * @return Result
	 */
	public function registerDelete(TrackedObject $trackedObject, Context $context = null): Result
	{
		$eventData = $trackedObject->prepareDeleteEventData();

		return $this->add(static::EVENT_TYPE_DELETE, $eventData, $context);
	}

	/**
	 * Creates a new 'update' record in the EventsTable. The objects are compared and found differences are registered.
	 *
	 * @param TrackedObject $trackedObject
	 * @param Context|null $context
	 * @return Result
	 */
	public function registerUpdate(TrackedObject $trackedObject, Context $context = null): Result
	{
		$eventData = $trackedObject->prepareUpdateEventData();

		return $this->add(static::EVENT_TYPE_UPDATE, $eventData, $context);
	}

	public function registerExport(TrackedObject $trackedObject, Context $context = null): Result
	{
		$eventData = $trackedObject->prepareExportEventData();

		return $this->add(static::EVENT_TYPE_EXPORT, $eventData, $context);
	}

	final public function registerBind(TrackedObject $parent, TrackedObject $child, Context $context = null): Result
	{
		$eventData = [];
		$eventData[] = $parent->prepareRelationEventData($child);
		$eventData[] = $child->prepareRelationEventData($parent);

		return $this->add(static::EVENT_TYPE_LINK, $eventData, $context);
	}

	final public function registerUnbind(TrackedObject $parent, TrackedObject $child, Context $context = null): Result
	{
		$eventData = [];
		$eventData[] = $parent->prepareRelationEventData($child);
		$eventData[] = $child->prepareRelationEventData($parent);

		return $this->add(static::EVENT_TYPE_UNLINK, $eventData, $context);
	}

	/**
	 * Adds new event record in the DB
	 *
	 * @param int $eventType
	 * @param EventHistoryData|EventHistoryData[] $eventData
	 * @param Context|null $context
	 * @return Result
	 */
	protected function add(int $eventType, $eventData, Context $context = null): Result
	{
		$result = new Result();

		if (is_array($eventData))
		{
			foreach ($eventData as $eventDataNested)
			{
				$localResult = $this->add($eventType, $eventDataNested, $context);
				if (!$localResult->isSuccess())
				{
					$result->addErrors($localResult->getErrors());
				}
			}

			return $result;
		}

		$eventDataArray = $eventData->toArray();

		$this->sendEvent('OnBeforeCrmAddEvent', $eventDataArray);

		$eventTableRecord = $this->createEventTableRecord($eventType, $context);
		/** @noinspection PhpParamsInspection */
		$eventTableResult = $this->saveRecord($eventTableRecord, $eventDataArray);
		if (!empty($eventTableResult->getErrors()))
		{
			$result->addErrors($eventTableResult->getErrors());
			return $result;
		}

		$eventRelationsTableRecord = $this->createEventRelationsTableRecord($eventTableResult->getId(), $context);
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
	 *
	 * @param int $eventType
	 * @param Context|null $context
	 * @return EO_Event
	 */
	protected function createEventTableRecord(int $eventType, Context $context = null): EO_Event
	{
		$context = $context ?? Container::getInstance()->getContext();

		// Empty strings by default because old API (CCrmEvent) was using them instead of NULL value
		return EventTable::createObject(
			[
				'CREATED_BY_ID' => $context->getUserId(),
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
		return $this->getEventTypeCaption($eventType);
	}

	/**
	 * Creates an EO_EventRelations object with default values set
	 *
	 * @param int $eventId
	 * @param Context|null $context
	 * @return EO_EventRelations
	 */
	protected function createEventRelationsTableRecord(int $eventId, Context $context = null): EO_EventRelations
	{
		$context = $context ?? Container::getInstance()->getContext();

		// Empty strings by default because old API (CCrmEvent) was using them instead of NULL value
		return EventRelationsTable::createObject(
			[
				// EVENT_ID in EventTable and EVENT_ID EventRelationsTable are absolutely different fields
				'EVENT_ID' => $eventId,
				'ENTITY_TYPE' => '',
				'ENTITY_ID' => 0,
				'ENTITY_FIELD' => '',
				'ASSIGNED_BY_ID' => $context->getUserId(),
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
