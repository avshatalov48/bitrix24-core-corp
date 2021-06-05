<?php

namespace Bitrix\Crm\Service\EventHistory;

class EventHistoryData
{
	private $storage = [
		'ENTITY_ID' => null,
		'ENTITY_FIELD' => null,
		'ENTITY_TYPE' => null,
		'EVENT_NAME' => null,
		'EVENT_TEXT_1' => null,
		'EVENT_TEXT_2' => null,
	];

	public function __construct(array $eventData = [])
	{
		$filteredEventData = array_intersect_key($eventData, $this->storage);
		$this->storage = $filteredEventData;
	}

	public function setEntityId(int $entityId): EventHistoryData
	{
		$this->storage['ENTITY_ID'] = $entityId;

		return $this;
	}

	public function getEntityId(): ?int
	{
		return $this->storage['ENTITY_ID'] ?? null;
	}

	public function setEntityField(string $entityField): EventHistoryData
	{
		$this->storage['ENTITY_FIELD'] = $entityField;

		return $this;
	}

	public function getEntityField(): ?string
	{
		return $this->storage['ENTITY_FIELD'] ?? null;
	}

	public function setEntityType(string $entityType): EventHistoryData
	{
		$this->storage['ENTITY_TYPE'] = $entityType;

		return $this;
	}

	public function getEntityType(): ?string
	{
		return $this->storage['ENTITY_TYPE'] ?? null;
	}

	public function setEventName(string $eventName): EventHistoryData
	{
		$this->storage['EVENT_NAME'] = $eventName;

		return $this;
	}

	public function getEventName(): ?string
	{
		return $this->storage['EVENT_NAME'] ?? null;
	}

	public function setEventTextFirst(string $eventTextFirst): EventHistoryData
	{
		$this->storage['EVENT_TEXT_1'] = $eventTextFirst;

		return $this;
	}

	public function getEventTextFirst(): ?string
	{
		return $this->storage['EVENT_TEXT_1'] ?? null;
	}

	public function setEventTextSecond(string $eventTextSecond): EventHistoryData
	{
		$this->storage['EVENT_TEXT_2'] = $eventTextSecond;

		return $this;
	}

	public function getEventTextSecond(): ?string
	{
		return $this->storage['EVENT_TEXT_2'] ?? null;
	}

	public function toArray(): array
	{
		return $this->storage;
	}
}