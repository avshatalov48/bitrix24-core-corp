<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Detector;

use Bitrix\Tasks\V2\Internal\Integration\Im\NotificationType;

class ChecklistOperation
{
	public function __construct(
		private readonly NotificationType $type,
		private readonly string $checklistName,
		private readonly int $itemCount = 1,
		private readonly ?string $assigneeName = null,
		private readonly ?int $assigneeId = null,
		private readonly array $additionalData = [],
		private readonly ?string $itemName = null
	) {}
	
	public function getType(): NotificationType
	{
		return $this->type;
	}
	
	public function getChecklistName(): string
	{
		return $this->checklistName;
	}
	
	public function getItemCount(): int
	{
		return $this->itemCount;
	}
	
	public function getAssigneeName(): ?string
	{
		return $this->assigneeName;
	}
	
	public function getAssigneeId(): ?int
	{
		return $this->assigneeId;
	}
	
	public function getItemName(): ?string
	{
		return $this->itemName;
	}
	
	public function getAdditionalData(): array
	{
		return $this->additionalData;
	}
}