<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI;

use ArrayAccess;
use ArrayIterator;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Task\Status;
use IteratorAggregate;

class TaskCollection implements IteratorAggregate, ArrayAccess
{
	protected array $tasks = [];
	protected array $parameters = [];
	protected string $type;

	public function __construct(array $tasks = [], string $type = 'all', array $parameters = [])
	{
		foreach ($tasks as $task)
		{
			$taskId = (int)$task['ID'];
			$this->tasks[$taskId] = $task;
		}

		$this->type = $type;
		$this->parameters = $parameters;
	}

	public function getGroupType(): string
	{
		return $this->parameters['groupType'] ?? '';
	}

	public function getIdList(): array
	{
		return array_map('intval', array_column($this->tasks, 'ID'));
	}

	public function getTitleList(): array
	{
		return array_column($this->tasks, 'TITLE');
	}

	/**
	 * @return static[]
	 */
	public function groupByEmployee(): array
	{
		$map = [];
		foreach ($this->tasks as $task)
		{
			$employeeId = (int)$task['RESPONSIBLE_ID'];
			$map[$employeeId][] = $task;
		}

		$collections = [];
		foreach  ($map as  $employeeId => $tasks)
		{
			$collections[$employeeId] = new static($tasks, $this->type, ['groupType' => 'employee']);
		}

		return $collections;
	}

	/**
	 * @return static[]
	 */
	public function groupByCreator(): array
	{
		$map = [];
		foreach ($this->tasks as $task)
		{
			$creatorId = (int)$task['CREATED_BY'];
			$map[$creatorId][] = $task;
		}

		$collections = [];
		foreach  ($map as $creatorId => $tasks)
		{
			$collections[$creatorId] = new static($tasks, $this->type, ['groupType' => 'creator']);
		}

		return $collections;
	}

	public function getByCreatorId(int $creatorId): static
	{
		$creatorsTasks = array_filter(
			$this->tasks,
			static fn(array $task): bool => (int)$task['CREATED_BY'] === $creatorId
		);

		return new static($creatorsTasks, $this->type);
	}

	public function getByEmployeeId(int $employeeId): static
	{
		$creatorsTasks = array_filter(
			$this->tasks,
			static fn(array $task): bool => (int)$task['RESPONSIBLE_ID'] === $employeeId
		);

		return new static($creatorsTasks, $this->type);
	}

	public function getEmployeeIdList(): array
	{
		$employees = array_column($this->tasks, 'RESPONSIBLE_ID');
		Collection::normalizeArrayValuesByInt($employees, false);

		return $employees;
	}

	public function getCreatorIdList(): array
	{
		$creators = array_column($this->tasks, 'CREATED_BY');
		Collection::normalizeArrayValuesByInt($creators, false);

		return $creators;
	}

	public function getById(int $id): ?array
	{
		return $this->tasks[$id]?? null;
	}

	public function getExpired(): static
	{
		$expired = array_filter(
			$this->tasks,
			static function(array $task): bool {
				$deadline = $task['DEADLINE'];
				if (!$deadline instanceof DateTime)
				{
					return false;
				}

				$closedDate = $task['CLOSED_DATE'];
				if (!$closedDate instanceof DateTime)
				{
					return time() > $deadline->getTimestamp();
				}

				return $closedDate->getTimestamp() > $deadline->getTimestamp();
			}
		);

		return new static($expired, $this->getCompositeType('overdue'));
	}

	public function getCompleted(): static
	{
		$completed = array_filter(
			$this->tasks,
			static fn(array $task): bool => (int)$task['REAL_STATUS'] === Status::COMPLETED
		);

		return new static($completed, $this->getCompositeType('completed'));
	}

	public function getNotExpired(): static
	{
		$notExpired = array_filter(
			$this->tasks,
			static function(array $task): bool {
				$deadline = $task['DEADLINE'];
				if (!$deadline instanceof DateTime)
				{
					return true;
				}

				$closedDate = $task['CLOSED_DATE'];
				if (!$closedDate instanceof DateTime)
				{
					return time() <= $deadline->getTimestamp();
				}

				return $closedDate->getTimestamp() <= $deadline->getTimestamp();
			}
		);

		return new static($notExpired, $this->getCompositeType('on_time'));
	}

	public function getType(): string
	{
		return $this->type . '_tasks';
	}

	public function getCount(): int
	{
		return count($this->tasks);
	}

	public function isEmpty(): bool
	{
		return empty($this->tasks);
	}

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->tasks);
	}

	private function getCompositeType(string $type): string
	{
		if ($this->type === 'all')
		{
			return  $type;
		}

		return $this->type . '_' .  $type;
	}

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->tasks[$offset]);
	}

	public function offsetGet(mixed $offset): ?array
	{
		return $this->tasks[$offset] ?? null;
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->tasks[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->tasks[$offset]);
	}
}
