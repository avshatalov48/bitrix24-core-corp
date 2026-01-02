<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Config;

class DeleteConfig
{
	private int $userId;
	private array $byPassParameters;
	private bool $skipExchangeSync;
	private ?string $eventGuid;
	private bool $skipBP;
	private RuntimeData $runtime;

	public function __construct(
		int $userId,
		array $byPassParameters = [],
		bool $skipExchangeSync = false,
		?string $eventGuid = null,
		bool $skipBP = false,
		RuntimeData $runtime = new RuntimeData()
	)
	{
		$this->userId = $userId;
		$this->byPassParameters = $byPassParameters;
		$this->skipExchangeSync = $skipExchangeSync;
		$this->eventGuid = $eventGuid;
		$this->skipBP = $skipBP;
		$this->runtime = $runtime;
	}

	public function getRuntime(): RuntimeData
	{
		return $this->runtime;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getByPassParameters(): array
	{
		return $this->byPassParameters;
	}

	public function isSkipExchangeSync(): bool
	{
		return $this->skipExchangeSync;
	}

	public function getEventGuid(): ?string
	{
		return $this->eventGuid;
	}

	public function isSkipBP(): bool
	{
		return $this->skipBP;
	}
}