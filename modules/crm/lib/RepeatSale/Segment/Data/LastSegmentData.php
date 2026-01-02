<?php

namespace Bitrix\Crm\RepeatSale\Segment\Data;

final class LastSegmentData implements SegmentDataInterface
{
	public function __construct(
		private readonly int $entityTypeId = \CCrmOwnerType::Contact,
		private readonly ?int $lastEntityId = null,
	)
	{
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function isLastDataForEntityTypeId(): bool
	{
		return true;
	}

	public function canProcessed(): bool
	{
		return true;
	}

	public function getItemsCount(): int
	{
		return 0;
	}

	public function getItems(): array
	{
		return [];
	}

	public function getLastAssignmentId(): ?int
	{
		return null;
	}

	public function getLastEntityId(): ?int
	{
		return $this->lastEntityId;
	}

	public function setLastAssignmentId(?int $lastAssignmentId): void
	{
		throw new \RuntimeException('Not implemented');
	}
}
