<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config;

use Bitrix\Tasks\Processor;

class AddConfig
{
	private int $userId;
	private bool $fromAgent;
	private bool $fromWorkFlow;
	private bool $checkFileRights;
	private bool $cloneAttachments;
	private array $byPassParameters;
	private bool $skipBP;
	private array $skipTimeZoneFields;
	private bool $needCorrectDatePlan;
	private ?string $eventGuid;
	private RuntimeData $runtime;
	private ?Processor\Task\Result $shiftResult = null;

	public function __construct(
		int $userId,
		bool $fromAgent = false,
		bool $fromWorkFlow = false,
		bool $checkFileRights = false,
		bool $cloneAttachments = false,
		array $byPassParameters = [],
		bool $skipBP = false,
		array $skipTimeZoneFields = [],
		bool $needCorrectDatePlan = true,
		?string $eventGuid = null,
		RuntimeData $runtime = new RuntimeData()
	) {
		$this->userId = $userId;
		$this->fromAgent = $fromAgent;
		$this->fromWorkFlow = $fromWorkFlow;
		$this->checkFileRights = $checkFileRights;
		$this->cloneAttachments = $cloneAttachments;
		$this->byPassParameters = $byPassParameters;
		$this->skipBP = $skipBP;
		$this->skipTimeZoneFields = $skipTimeZoneFields;
		$this->needCorrectDatePlan = $needCorrectDatePlan;
		$this->eventGuid = $eventGuid;
		$this->runtime = $runtime;
	}

	// Getter methods
	public function getUserId(): int
	{
		return $this->userId;
	}

	public function isFromAgent(): bool
	{
		return $this->fromAgent;
	}

	public function isFromWorkFlow(): bool
	{
		return $this->fromWorkFlow;
	}

	public function isCheckFileRights(): bool
	{
		return $this->checkFileRights;
	}

	public function isCloneAttachments(): bool
	{
		return $this->cloneAttachments;
	}

	public function getByPassParameters(): array
	{
		return $this->byPassParameters;
	}

	public function isSkipBP(): bool
	{
		return $this->skipBP;
	}

	public function getSkipTimeZoneFields(): array
	{
		return $this->skipTimeZoneFields;
	}

	public function isNeedCorrectDatePlan(): bool
	{
		return $this->needCorrectDatePlan;
	}

	public function getEventGuid(): ?string
	{
		return $this->eventGuid;
	}

	public function getFields(): array
	{
		return $this->fields;
	}

	public function getFullTaskData(): array
	{
		return $this->fullTaskData;
	}

	public function getTaskId(): int
	{
		return $this->fields['ID'];
	}

	public function getShiftResult(): ?Processor\Task\Result
	{
		return $this->shiftResult;
	}

	public function getRuntime(): RuntimeData
	{
		return $this->runtime;
	}

	// Fluent setters
	public function setUserId(int $userId): static
	{
		$this->userId = $userId;

		return $this;
	}

	public function setFromAgent(bool $fromAgent): static
	{
		$this->fromAgent = $fromAgent;

		return $this;
	}

	public function setFromWorkFlow(bool $fromWorkFlow): static
	{
		$this->fromWorkFlow = $fromWorkFlow;

		return $this;
	}

	public function setCheckFileRights(bool $checkFileRights): static
	{
		$this->checkFileRights = $checkFileRights;

		return $this;
	}

	public function setCloneAttachments(bool $cloneAttachments): static
	{
		$this->cloneAttachments = $cloneAttachments;

		return $this;
	}

	public function setByPassParameters(array $byPassParameters): static
	{
		$this->byPassParameters = $byPassParameters;

		return $this;
	}

	public function setSkipBP(bool $skipBP): static
	{
		$this->skipBP = $skipBP;

		return $this;
	}

	public function setSkipTimeZoneFields(array $skipTimeZoneFields): static
	{
		$this->skipTimeZoneFields = $skipTimeZoneFields;

		return $this;
	}

	public function setEventGuid(?string $eventGuid): static
	{
		$this->eventGuid = $eventGuid;

		return $this;
	}

	public function setFields(array $fields): static
	{
		$this->fields = $fields;

		return $this;
	}

	public function setFullTaskData(array $fullTaskData): static
	{
		$this->fullTaskData = $fullTaskData;

		return $this;
	}
}
