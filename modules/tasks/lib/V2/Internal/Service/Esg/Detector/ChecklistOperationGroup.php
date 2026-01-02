<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Detector;

use Bitrix\Tasks\V2\Internal\Integration\Im\NotificationType;

class ChecklistOperationGroup
{
	private array $operations = [];
	private array $operationCounts = [];

	public function addOperation(ChecklistOperation $operation): void
	{
		$this->operations[] = $operation;
		
		$type = $operation->getType()->value;
		if (!isset($this->operationCounts[$type]))
		{
			$this->operationCounts[$type] = 0;
		}
		$this->operationCounts[$type] += $operation->getItemCount();
	}

	public function hasMultipleOperationTypes(): bool
	{
		return count($this->operationCounts) > 1;
	}

	public function getOperations(): array
	{
		return $this->operations;
	}

	public function getOperationCounts(): array
	{
		return $this->operationCounts;
	}

	public function getCountForType(NotificationType $type): int
	{
		return $this->operationCounts[$type->value] ?? 0;
	}

	public function getOperationTypes(): array
	{
		return array_keys($this->operationCounts);
	}

	public function getTotalOperationCount(): int
	{
		return array_sum($this->operationCounts);
	}

	public function isEmpty(): bool
	{
		return empty($this->operations);
	}

	public function getOperationsByType(NotificationType $type): array
	{
		return array_filter($this->operations, fn(ChecklistOperation $operation) => $operation->getType() === $type);
	}
}