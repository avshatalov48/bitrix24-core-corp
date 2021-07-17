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

class CounterState implements \Iterator
{
	private static $instance;

	private $userId;
	private $state = [];
	private $counters = [
		CounterDictionary::META_PROP_ALL => [],
		CounterDictionary::META_PROP_PROJECT => [],
		CounterDictionary::META_PROP_GROUP => [],
		CounterDictionary::META_PROP_SONET => [],
		CounterDictionary::META_PROP_NONE => [],
	];

	/**
	 * @param int $userId
	 * @return static
	 */
	public static function getInstance(int $userId): self
	{
		if (
			!self::$instance
			|| !array_key_exists($userId, self::$instance)
		)
		{
			self::$instance[$userId] = new self($userId);
		}

		return self::$instance[$userId];
	}

	/**
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public static function reload(int $userId)
	{
		if (
			self::$instance
			&& array_key_exists($userId, self::$instance)
		)
		{
			$state = self::$instance[$userId];
			$state->loadCounters();
		}
	}

	/**
	 * CounterState constructor.
	 * @param int $userId
	 */
	private function __construct(int $userId)
	{
		$this->userId = $userId;
		$this->loadCounters();
	}

	/**
	 * @return bool
	 */
	public function isCounted(string $flag = CounterDictionary::COUNTER_FLAG_COUNTED): bool
	{
		if (empty($this->state))
		{
			return false;
		}

		foreach ($this->state as $row)
		{
			if ($row['TYPE'] === $flag)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $meta
	 * @return array
	 */
	public function getRawCounters(string $meta = CounterDictionary::META_PROP_ALL): array
	{
		if (!array_key_exists($meta, $this->counters))
		{
			return [];
		}
		return $this->counters[$meta];
	}

	/**
	 *
	 */
	public function rewind()
	{
		reset($this->state);
	}

	/**
	 * @return mixed
	 */
	public function current()
	{
		return current($this->state);
	}

	/**
	 * @return bool|float|int|string|null
	 */
	public function key()
	{
		return key($this->state);
	}

	/**
	 * @return mixed|void
	 */
	public function next()
	{
		return next($this->state);
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		$key = key($this->state);
		return ($key !== null && $key !== false);
	}

	/**
	 * @param array $rawCounters
	 */
	public function updateState(array $rawCounters, array $types = [], array $taskIds = []): void
	{
		if (empty($taskIds) && empty($types))
		{
			$this->state = [];
		}
		foreach ($this->state as $k => $row)
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

		$this->state = array_merge($this->state, $rawCounters);

		$this->updateRawCounters();
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
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function loadCounters(): void
	{
		$query = CounterTable::query()
			->setSelect([
				'VALUE',
				'TASK_ID',
				'GROUP_ID',
				'TYPE'
			])
			->where('USER_ID', $this->userId);

		$limit = Counter::getGlobalLimit();

		if ($limit === 0)
		{
			$query->where('TYPE', CounterDictionary::COUNTER_FLAG_COUNTED);
			$query->setLimit(1);
		}
		elseif (!is_null($limit))
		{
			$query->setLimit($limit);
			$query->addOrder('TASK_ID');
		}

		$rows = $query->exec()->fetchAll();

		$this->updateState($rows);
	}

	/**
	 *
	 */
	private function updateRawCounters(): void
	{
		$this->counters = [
			CounterDictionary::META_PROP_ALL => [],
			CounterDictionary::META_PROP_PROJECT => [],
			CounterDictionary::META_PROP_GROUP => [],
			CounterDictionary::META_PROP_SONET => [],
			CounterDictionary::META_PROP_NONE => []
		];

		$user = UserRegistry::getInstance($this->userId);
		$groups = $user->getUserGroups(UserRegistry::MODE_GROUP);
		$projects = $user->getUserGroups(UserRegistry::MODE_PROJECT);

		$tmpHeap[] = [];
		foreach ($this->state as $item)
		{
			if ($item['TYPE'] === CounterDictionary::COUNTER_FLAG_COUNTED)
			{
				continue;
			}

			$taskId = $item['TASK_ID'];
			$groupId = $item['GROUP_ID'];
			$value = $item['VALUE'];
			$type = $item['TYPE'];

			$meta = $this->getMetaProp($item, $groups, $projects);
			$subType = $this->getItemSubType($type);

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

			if (
				$subType === CounterDictionary::COUNTER_EXPIRED
				&& !isset($tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0][$taskId])
			)
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0] += $value;
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0] += $value;
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0] += $value;

				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] -= $value;
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] -= $value;
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] -= $value;

				$tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED][0][$taskId] = $value;
			}

			if (
				$subType === CounterDictionary::COUNTER_NEW_COMMENTS
				&& !isset($tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0][$taskId])
			)
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0] += $value;
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0] += $value;
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0] += $value;

				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] -= $value;
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] -= $value;
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] -= $value;

				$tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS][0][$taskId] = $value;
			}

			if (
				in_array($subType, [CounterDictionary::COUNTER_GROUP_EXPIRED, CounterDictionary::COUNTER_MUTED_EXPIRED])
				&& !isset($tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0][$taskId])
			)
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] += $value;
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] += $value;
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0] += $value;

				$tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED][0][$taskId] = $value;
			}

			if (
				in_array($subType, [CounterDictionary::COUNTER_GROUP_COMMENTS, CounterDictionary::COUNTER_MUTED_NEW_COMMENTS])
				&& !isset($tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0][$taskId])
			)
			{
				$this->counters[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] += $value;
				$this->counters[CounterDictionary::META_PROP_SONET][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] += $value;
				$this->counters[CounterDictionary::META_PROP_ALL][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0] += $value;

				$tmpHeap[$meta][CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS][0][$taskId] = $value;
			}
		}

		unset($tmpHeap);
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
	 * @return string
	 */
	private function getMetaProp(array $item, array $groups, array $projects): string
	{
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