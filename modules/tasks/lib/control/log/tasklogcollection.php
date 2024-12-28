<?php

namespace Bitrix\Tasks\Control\Log;

use ArrayIterator;
use Bitrix\Main\Type\Contract\Arrayable;
use Countable;
use IteratorAggregate;

/**
 * @method array getIdList()
 */
class TaskLogCollection implements IteratorAggregate, Arrayable, Countable
{
	/** @var TaskLog[] */
	private array $logs = [];

	public function __construct(TaskLog ...$logs)
	{
		foreach ($logs as $log)
		{
			$this->logs[$log->getId()] = $log;
		}
	}

	public function add(TaskLog $log): static
	{
		$this->logs[$log->getId()] = $log;

		return $this;
	}

	/** @return TaskLog[] */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->logs);
	}

	public function toArray(): array
	{
		return array_map(static fn (TaskLog $log): array => $log->toArray(), $this->logs);
	}

	public function getLogs(): array
	{
		return $this->logs;
	}

	public function count(): int
	{
		return count($this->logs);
	}

	public function isEmpty(): bool
	{
		return 0 === $this->count();
	}
}
