<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config;

use Bitrix\Tasks\Processor;

class RuntimeData
{
	private ?Processor\Task\Result $shiftResult;
	private array $legacyOperationResultData;
	private array $eventTaskData;
	private bool $isCommentAdded;

	public function __construct(
		?Processor\Task\Result $shiftResult = null,
		array $legacyOperationResultData = [],
		array $eventTaskData = [],
		bool $isCommentAdded = false,
	)
	{
		$this->shiftResult = $shiftResult;
		$this->legacyOperationResultData = $legacyOperationResultData;
		$this->eventTaskData = $eventTaskData;
		$this->isCommentAdded = $isCommentAdded;
	}

	public function getEventTaskData(): array
	{
		return $this->eventTaskData;
	}

	public function setEventTaskData(array $eventTaskData): RuntimeData
	{
		$this->eventTaskData = $eventTaskData;

		return $this;
	}

	public function getShiftResult(): ?Processor\Task\Result
	{
		return $this->shiftResult;
	}

	public function setShiftResult(?Processor\Task\Result $shiftResult): static
	{
		$this->shiftResult = $shiftResult;

		return $this;
	}

	public function getLegacyOperationResultData(): array
	{
		return $this->legacyOperationResultData;
	}

	public function setLegacyOperationResultData(string $key, mixed $value): static
	{
		$this->legacyOperationResultData[$key] = $value;

		return $this;
	}

	public function isCommentAdded(): bool
	{
		return $this->isCommentAdded;
	}

	public function setCommentAdded(bool $isCommentAdded): static
	{
		$this->isCommentAdded = $isCommentAdded;

		return $this;
	}
}