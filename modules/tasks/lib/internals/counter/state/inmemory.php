<?php

namespace Bitrix\Tasks\Internals\Counter\State;

use Bitrix\Tasks\Internals\Counter\CounterState;
use Bitrix\Tasks\Internals\Counter;

class InMemory extends CounterState
{
	private array $state = [];

	public function __construct(int $userId, Counter\Loader $loader)
	{
		parent::__construct($userId, $loader);
	}

	public function rewind(): void
	{
		reset($this->state);
	}

	public function current()
	{
		return current($this->state);
	}

	public function key()
	{
		return key($this->state);
	}

	public function next(): void
	{
		next($this->state);
	}

	public function valid(): bool
	{
		$key = key($this->state);
		return ($key !== null && $key !== false);
	}

	public function getSize(): int
	{
		return count($this->state);
	}

	public function updateState(array $rawCounters, array $types = [], array $taskIds = []): void
	{
		if (empty($taskIds) && empty($types))
		{
			$this->state = [];
		}
		foreach ($this as $k => $row)
		{
			if (
				!empty($taskIds)
				&& !in_array($row['TASK_ID'], $taskIds)
			)
			{
				continue;
			}

			if (
				!empty($types)
				&& !in_array($row['TYPE'], $types)
			)
			{
				continue;
			}
			unset($this->state[$k]);
		}
		foreach ($rawCounters as $k => $row)
		{
			if (is_null($row))
			{
				unset($rawCounters[$k]);
			}
		}

		$this->state = array_merge($this->state, $rawCounters);

		$this->updateRawCounters();
	}

	protected function loadCounters(): void
	{
		$this->updateState(
			$this->getLoader()->getRawCounters()
		);
	}
}