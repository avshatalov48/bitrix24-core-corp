<?php

namespace Bitrix\Crm\Service\EventHistory;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;

abstract class TrackedObject
{
	protected const ACTUAL = 'actual';
	protected const CURRENT = 'current';
	protected const ADD = 'add';
	protected const DELETE = 'delete';

	/** @var EntityObject|Item */
	protected $objectBeforeSave;
	/** @var EntityObject|Item */
	protected $object;

	/** @var string */
	protected $entityType;
	/** @var string */
	protected $entityDescription;

	/** @var TrackedObject[] */
	protected $dependantTrackedObjects = [];

	protected $ownerFieldName;
	protected $ownerId;

	public function __construct(object $objectBeforeSave = null, object $object = null)
	{
		$this->objectBeforeSave = $objectBeforeSave;
		$this->object = $object;

		Loc::loadLanguageFile(__FILE__);
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function bindToEntityType(string $entityType, string $entityDescription): TrackedObject
	{
		$this->entityType = $entityType;
		$this->entityDescription = $entityDescription;

		return $this;
	}

	public function makeThisObjectDependant(string $ownerFieldName): TrackedObject
	{
		if (empty($ownerFieldName))
		{
			throw new ArgumentException('ownerFieldName can\'t be an empty string', 'ownerFieldName');
		}

		$this->ownerFieldName = $ownerFieldName;

		return $this;
	}

	public function addDependantTrackedObject(TrackedObject $dependant): TrackedObject
	{
		if (!$dependant->isThisObjectDependant())
		{
			throw new ArgumentException('Cant add not dependant TrackedObject as dependant', 'dependant');
		}

		$this->dependantTrackedObjects[] = $dependant;

		return $this;
	}

	protected function isThisObjectDependant(): bool
	{
		return !is_null($this->ownerFieldName);
	}

	protected function isDependantField(string $fieldName): bool
	{
		return !is_null($this->getDependantTrackedObject($fieldName));
	}

	protected function getDependantTrackedObject(string $dependantFieldName): ?TrackedObject
	{
		foreach ($this->dependantTrackedObjects as $dependant)
		{
			if ($dependant->ownerFieldName === $dependantFieldName)
			{
				return $dependant;
			}
		}

		return null;
	}

	/**
	 * @return string[]
	 */
	protected function getTrackedFieldNames(): array
	{
		$fieldNames = array_merge($this->getTrackedRegularFieldNames(), $this->getTrackedDependantFieldNames());

		return array_unique($fieldNames);
	}

	/**
	 * Returns names of tracked not dependant fields
	 *
	 * @return string[]
	 */
	abstract protected function getTrackedRegularFieldNames(): array;

	protected function getTrackedDependantFieldNames(): array
	{
		$trackedFieldNamesOfThisEntity = [];
		foreach ($this->dependantTrackedObjects as $dependant)
		{
			$trackedFieldNamesOfThisEntity[] = $dependant->ownerFieldName;
		}

		return $trackedFieldNamesOfThisEntity;
	}

	protected function getActualValue(string $fieldName)
	{
		return $this->objectBeforeSave->remindActual($fieldName);
	}

	protected function getCurrentValue(string $fieldName)
	{
		return $this->object->get($fieldName);
	}

	public function getEntityId(): int
	{
		return $this->objectBeforeSave->getId();
	}

	public function getEntityType(): string
	{
		return $this->entityType;
	}

	protected function getEntityTitle(): string
	{
		$methodName = static::getEntityTitleMethod();
		return (string)$this->objectBeforeSave->$methodName();
	}

	/**
	 * Returns name of the EntityObject|Item method, that is used to get entity's title
	 *
	 * @return string
	 */
	abstract protected static function getEntityTitleMethod(): string;

	public function prepareViewEventData(): EventHistoryData
	{
		return new EventHistoryData([
			'ENTITY_TYPE' => $this->getEntityType(),
			'ENTITY_ID' => $this->getEntityId(),
		]);
	}

	public function prepareDeleteEventData(): EventHistoryData
	{
		$entityId = $this->getEntityId();
		$title = $this->getEntityTitle();

		return new EventHistoryData([
			'ENTITY_TYPE' => \CCrmOwnerType::SystemName,
			'ENTITY_ID' => 0,
			'EVENT_TEXT_1' => "$this->entityDescription: [$entityId] $title",
		]);
	}

	public function prepareExportEventData(): EventHistoryData
	{
		return new EventHistoryData([
			'ENTITY_TYPE' => $this->getEntityType(),
			'ENTITY_ID' => $this->getEntityId(),
		]);
	}

	/**
	 * @return EventHistoryData[]
	 * @throws ArgumentException
	 */
	public function prepareUpdateEventData(): array
	{
		$eventDataArrays = [];
		foreach ($this->getTrackedFieldNames() as $trackedFieldName)
		{
			$eventData = $this->prepareUpdateEventDataForField($trackedFieldName);

			if ($this->isThisObjectDependant())
			{
				$eventData = $this->modifyEventDataAsDependant($eventData);
			}

			$eventDataArrays[] = $eventData;
		}

		return $this->normalizeEventDataArrays($eventDataArrays);
	}

	/**
	 * @param EventHistoryData|EventHistoryData[] $eventData
	 * @return EventHistoryData|EventHistoryData[]
	 */
	protected function modifyEventDataAsDependant($eventData)
	{
		if (is_array($eventData))
		{
			foreach ($eventData as &$eventDataElement)
			{
				$eventDataElement = $this->modifyEventDataAsDependant($eventDataElement);
			}
		}
		elseif (is_object($eventData))
		{
			$eventData = $this->modifySingleEventHistoryData($eventData);
		}

		return $eventData;
	}

	protected function modifySingleEventHistoryData(EventHistoryData $eventData): EventHistoryData
	{
		$fieldNameOfDependantEntity = $eventData->getEntityField();
		$eventData->setEventName($this->getDependantUpdateEventName($fieldNameOfDependantEntity));

		$eventData->setEntityId($this->ownerId);
		$eventData->setEntityField($this->ownerFieldName);

		return $eventData;
	}

	protected function getDependantUpdateEventName(string $fieldName): string
	{
		return Loc::getMessage(
			'CRM_TRACKED_OBJECT_DEPENDANT_UPDATE_TEXT',
			['#FIELD_NAME#' => $this->getFieldNameCaption($fieldName), '#ENTITY_NAME#' => $this->ownerFieldName]
		);
	}

	/**
	 * @param array[] $eventDataArrays
	 *
	 * @return EventHistoryData[]
	 */
	protected function normalizeEventDataArrays(array $eventDataArrays): array
	{
		if (empty($eventDataArrays))
		{
			return [];
		}

		return array_merge(...$eventDataArrays);
	}

	/**
	 * @param string $fieldName
	 *
	 * @return EventHistoryData[]
	 * @throws ArgumentException
	 */
	protected function prepareUpdateEventDataForField(string $fieldName): array
	{
		if ($this->isDependantField($fieldName))
		{
			$actualCollection = $this->normalizeCollection($this->getActualValue($fieldName));
			$currentCollection = $this->normalizeCollection($this->getCurrentValue($fieldName));

			return $this->prepareUpdateEventDataForDependantField($fieldName, $actualCollection, $currentCollection);
		}

		if ($this->isChanged($fieldName))
		{
			return [
				$this->prepareDefaultUpdateEventData($fieldName)
			];
		}

		return [];
	}

	protected function isChanged(string $fieldName): bool
	{
		$actualValue = $this->getActualValue($fieldName);
		$currentValue = $this->getCurrentValue($fieldName);

		if (is_scalar($actualValue) && is_scalar($currentValue))
		{
			return $actualValue !== $currentValue;
		}

		// Loose comparison to deem cloned objects with same values as equal
		/** @noinspection TypeUnsafeComparisonInspection */
		return $actualValue != $currentValue;
	}

	protected function prepareDefaultUpdateEventData(string $fieldName): EventHistoryData
	{
		$actualValue = $this->getActualValue($fieldName);
		$currentValue = $this->getCurrentValue($fieldName);

		return new EventHistoryData([
			'ENTITY_TYPE' => $this->getEntityType(),
			'ENTITY_ID' => $this->getEntityId(),
			'ENTITY_FIELD' => $fieldName,
			'EVENT_NAME' => $this->getUpdateEventName($fieldName),
			'EVENT_TEXT_1' => $this->getFieldValueCaption($fieldName, $actualValue, static::ACTUAL),
			'EVENT_TEXT_2' => $this->getFieldValueCaption($fieldName, $currentValue, static::CURRENT),
		]);
	}

	protected function getUpdateEventName(string $fieldName): string
	{
		return (string)Loc::getMessage(
			'CRM_TRACKED_OBJECT_UPDATE_EVENT_NAME',
			['#FIELD_NAME#' => $this->getFieldNameCaption($fieldName)]
		);
	}

	/**
	 * @param Collection|EntityObject[]|Item[]|null $collection
	 *
	 * @return array
	 * @throws ArgumentException
	 */
	protected function normalizeCollection($collection): array
	{
		if (is_null($collection))
		{
			return [];
		}

		if (!$this->isCollection($collection))
		{
			throw new ArgumentException(
				'Collection should be an instance of '.Collection::class.' or array of '.EntityObject::class.' ('.Item::class.')'
			);
		}

		if ($collection instanceof Collection)
		{
			$collection = $collection->getAll();
		}

		return $collection;
	}

	protected function isCollection($value): bool
	{
		if ($value instanceof Collection)
		{
			return true;
		}

		if (is_array($value))
		{
			if (empty($value))
			{
				return true;
			}

			$firstElement = array_shift($value);

			return ( ($firstElement instanceof EntityObject) || ($firstElement instanceof Item) );
		}

		return false;
	}

	/**
	 * @param string $fieldName
	 * @param EntityObject[]|Item[] $actualCollection
	 * @param EntityObject[]|Item[] $currentCollection
	 *
	 * @return EventHistoryData[]
	 * @throws ArgumentException
	 */
	protected function prepareUpdateEventDataForDependantField(string $fieldName, array $actualCollection, array $currentCollection): array
	{
		return array_merge(
			$this->prepareUpdateEventDataForChangedObjects($fieldName, $actualCollection, $currentCollection),
			$this->prepareUpdateEventDataForAddedOrDeletedObjects($fieldName, $actualCollection, $currentCollection)
		);
	}

	/**
	 * @param string $fieldName
	 * @param array $actualCollection
	 * @param array $currentCollection
	 *
	 * @return EventHistoryData[]
	 * @throws ArgumentException
	 */
	protected function prepareUpdateEventDataForChangedObjects(string $fieldName, array $actualCollection, array $currentCollection): array
	{
		$eventDataArrays = [];

		/** @var Item|EntityObject $currentEntity */
		foreach ($currentCollection as $currentEntity)
		{
			$actualEntity = $this->findEntityInCollectionByPrimary($actualCollection, $currentEntity);
			if (!$actualEntity)
			{
				continue;
			}

			$dependantTrackedObject = $this->getDependantTrackedObject($fieldName);
			$dependantTrackedObject->objectBeforeSave = $actualEntity;
			$dependantTrackedObject->object = $currentEntity;
			$dependantTrackedObject->bindToEntityType($this->entityType, $this->entityDescription);
			$dependantTrackedObject->ownerId = $this->getEntityId();

			$eventDataArrays[] = $dependantTrackedObject->prepareUpdateEventData();
		}

		return $this->normalizeEventDataArrays($eventDataArrays);
	}

	/**
	 * Find an entity object that has the same primary as the $entityToFind in the collection
	 *
	 * @param EntityObject[]|Item[] $entitiesCollection
	 * @param object|Item|EntityObject $entityToFind
	 *
	 * @return object|EntityObject|Item|null
	 */
	protected function findEntityInCollectionByPrimary(array $entitiesCollection, object $entityToFind): ?object
	{
		foreach ($entitiesCollection as $entity)
		{
			if ($entity->primary === $entityToFind->primary)
			{
				return $entity;
			}
		}

		return null;
	}

	/**
	 * @param string $fieldName
	 * @param EntityObject[]|Item[] $actualCollection
	 * @param EntityObject[]|Item[] $currentCollection
	 *
	 * @return EventHistoryData[]
	 */
	protected function prepareUpdateEventDataForAddedOrDeletedObjects(string $fieldName, array $actualCollection, array $currentCollection): array
	{
		$added = $this->getDifferenceBetweenCollections($currentCollection, $actualCollection);
		$deleted = $this->getDifferenceBetweenCollections($actualCollection, $currentCollection);

		$eventData = [];

		if (!empty($added))
		{
			$eventData[] = $this->prepareEventDataForEntity($fieldName, $added, static::ADD);
		}

		if (!empty($deleted))
		{
			$eventData[] = $this->prepareEventDataForEntity($fieldName, $deleted, static::DELETE);
		}

		return $eventData;
	}

	/**
	 * Returns object that present in the firstCollection and absent in the secondCollection
	 *
	 * @param EntityObject[]|Item[] $firstCollection
	 * @param EntityObject[]|Item[] $secondCollection
	 *
	 * @return EntityObject[]|Item[]
	 */
	protected function getDifferenceBetweenCollections(array $firstCollection, array $secondCollection): array
	{
		$difference = [];

		foreach ($firstCollection as $firstEntity)
		{
			$entityInAnotherCollection = $this->findEntityInCollectionByPrimary($secondCollection, $firstEntity);
			if (is_null($entityInAnotherCollection))
			{
				$difference[] = $firstEntity;
			}
		}

		return $difference;
	}

	protected function prepareEventDataForEntity(string $fieldName, array $objects, string $addOrDelete): EventHistoryData
	{
		$methodName = $this->getDependantTrackedObject($fieldName)::getEntityTitleMethod();

		$names = [];
		foreach ($objects as $object)
		{
			$names[] = $object->$methodName();
		}

		return new EventHistoryData([
			'ENTITY_TYPE' => $this->getEntityType(),
			'ENTITY_FIELD' => $fieldName,
			'EVENT_NAME' => $this->getEntityAddOrDeleteEventName($fieldName, $addOrDelete),
			'EVENT_TEXT_1' => implode(', ', $names),
			'ENTITY_ID' => $this->getEntityId(),
		]);
	}

	protected function getEntityAddOrDeleteEventName(string $fieldName, string $addOrDelete): string
	{
		if ($addOrDelete === static::ADD)
		{
			return Loc::getMessage('CRM_TRACKED_OBJECT_ENTITY_ADD_EVENT_NAME', ['#FIELD_NAME#' => $this->getFieldNameCaption($fieldName)]);
		}

		if ($addOrDelete === static::DELETE)
		{
			return Loc::getMessage('CRM_TRACKED_OBJECT_ENTITY_DELETE_EVENT_NAME', ['#FIELD_NAME#' => $this->getFieldNameCaption($fieldName)]);
		}

		return '';
	}

	protected function getFieldNameCaption(string $fieldName): string
	{
		return $fieldName;
	}

	/** @noinspection PhpUnusedParameterInspection */
	protected function getFieldValueCaption(string $fieldName, $fieldValue, string $actualOrCurrent = null): string
	{
		return (string)$fieldValue;
	}
}
