<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config;

use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Processor;

class RuntimeData
{
	private ?Processor\Task\Result $shiftResult;
	private bool $isCommentAdded;
	private array $fields;
	private array $fullTaskData;
	private TaskObject $task;

	public function __construct(
		?Processor\Task\Result $shiftResult = null,
		bool $isCommentAdded = false,
	)
	{
		$this->shiftResult = $shiftResult;
		$this->isCommentAdded = $isCommentAdded;
	}

	public function getShiftResult(): ?Processor\Task\Result
	{
		return $this->shiftResult;
	}

	public function setShiftResult(?Processor\Task\Result $shiftResult): RuntimeData
	{
		$this->shiftResult = $shiftResult;

		return $this;
	}

	public function isCommentAdded(): bool
	{
		return $this->isCommentAdded;
	}

	public function setIsCommentAdded(bool $isCommentAdded): RuntimeData
	{
		$this->isCommentAdded = $isCommentAdded;

		return $this;
	}

	public function getFields(): array
	{
		return $this->fields;
	}

	public function setFields(array $fields): RuntimeData
	{
		$this->fields = $fields;

		return $this;
	}

	public function getFullTaskData(): array
	{
		return $this->fullTaskData;
	}

	public function setFullTaskData(array $fullTaskData): RuntimeData
	{
		$this->fullTaskData = $fullTaskData;

		return $this;
	}

	public function getTask(): TaskObject
	{
		return $this->task;
	}

	public function setTask(TaskObject $task): RuntimeData
	{
		$this->task = $task;

		return $this;
	}
}