<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Robot;

use ArrayIterator;
use IteratorAggregate;

class RobotCommandCollection implements IteratorAggregate
{
	protected int $stageId;
	protected string $type;
	protected array $commands;

	public function __construct(int $stageId, string $type, AbstractRobotCommand ...$commands)
	{
		$this->stageId = $stageId;
		$this->type = $type;
		$this->commands = $commands;
	}

	public function add(AbstractRobotCommand $command): static
	{
		$this->commands[] = $command;
		return $this;
	}

	public function getStageId(): int
	{
		return $this->stageId;
	}

	public function getStageType(): string
	{
		return $this->type;
	}

	public function getUserSensitive(): static
	{
		$commands = array_filter(
			$this->commands,
			static fn (AbstractRobotCommand $command): bool => $command->isUserSensitive()
		);
		return new static ($this->stageId, $this->type, ...$commands);
	}

	public function isEmpty(): bool
	{
		return empty($this->commands);
	}

	public function toArray(): array
	{
		return array_map(static fn (AbstractRobotCommand $command) => $command->toArray(), $this->commands);
	}

	/**
	 * @return AbstractRobotCommand[]
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->commands);
	}
}