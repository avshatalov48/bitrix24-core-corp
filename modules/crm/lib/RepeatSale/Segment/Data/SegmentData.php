<?php

namespace Bitrix\Crm\RepeatSale\Segment\Data;

final class SegmentData implements SegmentDataInterface
{
	public function __construct(
		private readonly array $items = [],
		private readonly int $entityTypeId = \CCrmOwnerType::Contact,
		private readonly ?int $lastEntityId = null,
		private ?int $lastAssignmentId = null,
	)
	{
	}

	public function getItems(): array
	{
		return $this->items;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getLastEntityId(): ?int
	{
		return $this->lastEntityId;
	}

	public function getLastAssignmentId(): ?int
	{
		return $this->lastAssignmentId;
	}

	public function setLastAssignmentId(?int $lastAssignmentId): void
	{
		$this->lastAssignmentId = $lastAssignmentId;
	}

	public function isLastDataForEntityTypeId(): bool
	{
		return false;
	}

	public function canProcessed(): bool
	{
		return true;
	}

	public function getItemsCount(): int
	{
		return count($this->items);
	}
}
