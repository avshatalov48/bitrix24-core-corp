<?php

namespace Bitrix\Crm\RepeatSale\Segment\Data;

class WrongSegmentData implements SegmentDataInterface
{
	public function isLastDataForEntityTypeId(): bool
	{
		return true;
	}

	public function canProcessed(): bool
	{
		return false;
	}

	public function getEntityTypeId(): int
	{
		throw new \RuntimeException('Not implemented');
	}

	public function getItemsCount(): int
	{
		throw new \RuntimeException('Not implemented');
	}

	public function getItems(): array
	{
		throw new \RuntimeException('Not implemented');
	}

	public function getLastAssignmentId(): ?int
	{
		throw new \RuntimeException('Not implemented');
	}

	public function getLastEntityId(): ?int
	{
		throw new \RuntimeException('Not implemented');
	}

	public function setLastAssignmentId(?int $lastAssignmentId): void
	{
		throw new \RuntimeException('Not implemented');
	}
}
