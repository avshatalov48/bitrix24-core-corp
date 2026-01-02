<?php

namespace Bitrix\Crm\RepeatSale\Segment\Data;

interface SegmentDataInterface
{
	public function isLastDataForEntityTypeId(): bool;
	public function canProcessed(): bool;
	public function getEntityTypeId(): int;
	public function getItemsCount(): int;
	public function getItems(): array;
	public function getLastAssignmentId(): ?int;
	public function getLastEntityId(): ?int;
	public function setLastAssignmentId(?int $lastAssignmentId): void;
}
