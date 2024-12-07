<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Registry\UserRegistry;

abstract class CounterState implements \Iterator
{
	private Counter\Loader $loader;

	protected int $userId;
	protected array $counters = [];

	/**
	 * CounterState constructor.
	 * @param int $userId
	 */
	protected function __construct(int $userId, Counter\Loader $loader)
	{
		$this->userId = $userId;
		$this->loader = $loader;
		$this->init();
	}

	abstract public function rewind(): void;

	#[\ReturnTypeWillChange]
	abstract public function current();

	#[\ReturnTypeWillChange]
	abstract public function key();

	abstract public function next(): void;

	abstract public function valid(): bool;

	abstract public function getSize(): int;

	abstract protected function loadCounters(): void;

	abstract public function updateState(array $rawCounters, array $types = [], array $taskIds = []): void;

	public function init(): void
	{
		$this->counters = $this->getCountersEmptyState();
		$this->loadCounters();
	}

	public function getLoader(): Counter\Loader
	{
		return $this->loader;
	}

	public function isCounted(): bool
	{
		return $this->loader->isCounted();
	}

	public function getClearedDate(): int
	{
		return $this->loader->getClearedDate();
	}

	public function resetCache(): void
	{
		$this->loader->resetCache();
	}

	/**
	 * @param string $meta
	 * @return array
	 */
	public function getRawCounters(string $meta = CounterDictionary::META_PROP_ALL): array
	{
		return $this->counters[$meta] ?? [];
	}

	/**
	 * @param string $name
	 * @param int|null $groupId
	 * @return int
	 */
	public function getValue(string $name, int $groupId = null): int
	{
		$counters = $this->counters[CounterDictionary::META_PROP_ALL];

		if ($groupId > 0)
		{
			if (
				!array_key_exists($name, $counters)
				|| !array_key_exists($groupId, $counters[$name])
			)
			{
				return 0;
			}

			return $counters[$name][$groupId];
		}

		if (!array_key_exists($name, $counters))
		{
			return 0;
		}

		return array_sum($counters[$name]);
	}

	/**
	 * Updates counters based on current state
	 * @return void
	 */
	protected function updateRawCounters(): void
	{
		$this->counters = $this->getCountersEmptyState();

		$user = UserRegistry::getInstance($this->userId);
		$groups = $user->getUserGroups(UserRegistry::MODE_GROUP);
		$projects = $user->getUserGroups(UserRegistry::MODE_PROJECT);
		$scrum = $user->getUserGroups(UserRegistry::MODE_SCRUM);

		$tmpHeap[] = [];
		foreach ($this as $item)
		{
			if ($this->getLoader()->isCounterFlag($item['TYPE']))
			{
				continue;
			}

			$taskId = $item['TASK_ID'];
			$groupId = $item['GROUP_ID'];
			$value = $item['VALUE'];
			$type = $item['TYPE'];
			$flowId = $item['FLOW_ID'] ?? 0;

			$meta = $this->getMetaProp($item, $groups, $projects, $scrum);
			$subType = $this->getItemSubType($type);

			if (!isset($this->counters[$meta][$type][$groupId]))
			{
				$this->counters[$meta][$type][$groupId] = 0;
			}
			if (!isset($this->counters[$meta][$subType][$groupId]))
			{
				$this->counters[$meta][$subType][$groupId] = 0;
			}
			if (!isset($tmpHeap[$meta][$subType][$groupId]))
			{
				$tmpHeap[$meta][$subType][$groupId] = [];
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_SONET][$type][$groupId]))
			{
				$this->counters[CounterDictionary::META_PROP_SONET][$type][$groupId] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_SONET][$subType][$groupId]))
			{
				$this->counters[CounterDictionary::META_PROP_SONET][$subType][$groupId] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_ALL][$type][$groupId]))
			{
				$this->counters[CounterDictionary::META_PROP_ALL][$type][$groupId] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_ALL][$subType][$groupId]))
			{
				$this->counters[CounterDictionary::META_PROP_ALL][$subType][$groupId] = 0;
			}

			// flow
			if (
				$flowId
				&& in_array($type, CounterDictionary::FLOW_TYPES)
				&& !isset($tmpHeap[CounterDictionary::META_PROP_FLOW][$type][$flowId][$taskId]))
			{
				$tmpHeap[CounterDictionary::META_PROP_FLOW][$type][$flowId][$taskId] = $value;
				$currentTypeValue = $this->counters[CounterDictionary::META_PROP_FLOW][$type][$flowId] ?? 0;
				$this->counters[CounterDictionary::META_PROP_FLOW][$type][$flowId] = $currentTypeValue + $value;
				// common flow
				$commonFlowValue = $this->counters[CounterDictionary::META_PROP_FLOW][$type][0] ?? 0;
				$this->counters[CounterDictionary::META_PROP_FLOW][$type][0] = $commonFlowValue + $value;
			}

			if (!isset($tmpHeap[$meta][$type][$groupId][$taskId]))
			{
				$tmpHeap[$meta][$type][$groupId][$taskId] = $value;
				$this->counters[$meta][$type][$groupId] += $value;

				if (in_array($meta, [CounterDictionary::META_PROP_GROUP, CounterDictionary::META_PROP_PROJECT]))
				{
					$this->counters[CounterDictionary::META_PROP_SONET][$type][$groupId] += $value;
				}
			}

			if (
				$type !== $subType
				&& !isset($tmpHeap[$meta][$subType][$groupId][$taskId])
			)
			{
				$tmpHeap[$meta][$subType][$groupId][$taskId] = $value;
				$this->counters[$meta][$subType][$groupId] += $value;

				if (in_array($meta, [CounterDictionary::META_PROP_GROUP, CounterDictionary::META_PROP_PROJECT]))
				{
					$this->counters[CounterDictionary::META_PROP_SONET][$subType][$groupId] += $value;
				}
			}

			if (!isset($tmpHeap[CounterDictionary::META_PROP_ALL][$type][$groupId][$taskId]))
			{
				$tmpHeap[CounterDictionary::META_PROP_ALL][$type][$groupId][$taskId] = $value;
				$this->counters[CounterDictionary::META_PROP_ALL][$type][$groupId] += $value;
			}

			if (
				$type !== $subType
				&& !isset($tmpHeap[CounterDictionary::META_PROP_ALL][$subType][$groupId][$taskId])
			)
			{
				$tmpHeap[CounterDictionary::META_PROP_ALL][$subType][$groupId][$taskId] = $value;
				$this->counters[CounterDictionary::META_PROP_ALL][$subType][$groupId] += $value;
			}

			if ($meta === CounterDictionary::META_PROP_NONE)
			{
				continue;
			}

			// Total sum for the all groups/projects
			if (!isset($this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0]))
			{
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0]))
			{
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0]))
			{
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0]))
			{
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0]))
			{
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0]))
			{
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0]))
			{
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] = 0;
			}
			if (!isset($this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0]))
			{
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] = 0;
			}
			if (!isset($this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0]))
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] = 0;
			}
			if (!isset($this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0]))
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] = 0;
			}
			if (!isset($this->counters[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0]))
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0] = 0;
			}

			if (
				$subType === CounterDictionary::COUNTER_EXPIRED
				&& !isset($tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0][$taskId])
			)
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0] += $value;
				if (in_array($meta, [CounterDictionary::META_PROP_PROJECT, CounterDictionary::META_PROP_GROUP]))
				{
					$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0] += $value;
					$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0] += $value;
				}

				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] -= $value;
				if (in_array($meta, [CounterDictionary::META_PROP_PROJECT, CounterDictionary::META_PROP_GROUP]))
				{
					$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] -= $value;
					$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] -= $value;
				}

				$tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0][$taskId] = $value;
			}

			if (
				$subType === CounterDictionary::COUNTER_NEW_COMMENTS
				&& !isset($tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0][$taskId])
			)
			{
				if (!isset($this->counters[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0]))
				{
					$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0] = 0;
				}
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0] += $value;
				if (in_array($meta, [CounterDictionary::META_PROP_PROJECT, CounterDictionary::META_PROP_GROUP]))
				{
					$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0] += $value;
					$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0] += $value;
				}

				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] -= $value;
				if (in_array($meta, [CounterDictionary::META_PROP_PROJECT, CounterDictionary::META_PROP_GROUP]))
				{
					$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] -= $value;
					$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] -= $value;
				}

				$tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0][$taskId] = $value;
			}

			if (
				in_array($subType, [CounterDictionary::COUNTER_GROUP_EXPIRED, CounterDictionary::COUNTER_MUTED_EXPIRED])
				&& !isset($tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0][$taskId])
			)
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] += $value;
				if (in_array($meta, [CounterDictionary::META_PROP_PROJECT, CounterDictionary::META_PROP_GROUP]))
				{
					$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] += $value;
					$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] += $value;
				}

				$tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0][$taskId] = $value;
			}

			if (
				in_array($subType, [CounterDictionary::COUNTER_GROUP_COMMENTS, CounterDictionary::COUNTER_MUTED_NEW_COMMENTS])
				&& !isset($tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0][$taskId])
			)
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] += $value;
				if (in_array($meta, [CounterDictionary::META_PROP_PROJECT, CounterDictionary::META_PROP_GROUP]))
				{
					$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] += $value;
					$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] += $value;
				}

				$tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0][$taskId] = $value;
			}
		}

		unset($tmpHeap);
	}

	private function getCountersEmptyState(): array
	{
		return [
			CounterDictionary::META_PROP_ALL => [],
			CounterDictionary::META_PROP_PROJECT => [],
			CounterDictionary::META_PROP_GROUP => [],
			CounterDictionary::META_PROP_SONET => [],
			CounterDictionary::META_PROP_SCRUM => [],
			CounterDictionary::META_PROP_FLOW => [],
			CounterDictionary::META_PROP_NONE => [],
		];
	}

	/**
	 * @param array $item
	 * @return string
	 */
	private function getItemSubType(string $type): string
	{
		if (in_array($type, CounterDictionary::MAP_EXPIRED))
		{
			return CounterDictionary::COUNTER_EXPIRED;
		}

		if (in_array($type, CounterDictionary::MAP_MUTED_EXPIRED))
		{
			return CounterDictionary::COUNTER_MUTED_EXPIRED;
		}

		if (in_array($type, CounterDictionary::MAP_COMMENTS))
		{
			return CounterDictionary::COUNTER_NEW_COMMENTS;
		}

		if (in_array($type, CounterDictionary::MAP_MUTED_COMMENTS))
		{
			return CounterDictionary::COUNTER_MUTED_NEW_COMMENTS;
		}

		return $type;
	}

	/**
	 * @param array $item
	 * @param array $groups
	 * @param array $projects
	 * @param array $scrum
	 * @return string
	 */
	private function getMetaProp(array $item, array $groups, array $projects, array $scrum): string
	{
		if (array_key_exists($item['GROUP_ID'], $scrum))
		{
			return CounterDictionary::META_PROP_SCRUM;
		}

		if (array_key_exists($item['GROUP_ID'], $groups))
		{
			return CounterDictionary::META_PROP_GROUP;
		}

		if (array_key_exists($item['GROUP_ID'], $projects))
		{
			return CounterDictionary::META_PROP_PROJECT;
		}

		return CounterDictionary::META_PROP_NONE;
	}
}