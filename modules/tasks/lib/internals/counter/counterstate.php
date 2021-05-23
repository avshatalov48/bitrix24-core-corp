<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter;


use Bitrix\Main\Application;

class CounterState implements \Iterator
{
	private static $instance;

	private $userId;
	private $state = [];
	private $counters = [];

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
	 * CounterState constructor.
	 * @param int $userId
	 */
	private function __construct(int $userId)
	{
		$this->userId = $userId;
		$this->loadCounters();
	}

	/**
	 *
	 */
	public function reload()
	{
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
	 * @return array
	 */
	public function getRawCounters(): array
	{
		return $this->counters;
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
		$this->updateUserCounters();
	}

	/**
	 * @param string $name
	 * @param int|null $groupId
	 * @return int
	 */
	public function getValue(string $name, int $groupId = null): int
	{
		if ($groupId > 0)
		{
			if (
				!array_key_exists($name, $this->counters)
				|| !array_key_exists($groupId, $this->counters[$name])
			)
			{
				return 0;
			}

			return $this->counters[$name][$groupId];
		}

		if (!array_key_exists($name, $this->counters))
		{
			return 0;
		}

		return array_sum($this->counters[$name]);
	}

	/**
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function loadCounters(): void
	{
		$res = Application::getConnection()->query("
			SELECT 
				`VALUE`,
			   	TASK_ID,
		   		GROUP_ID,
			   	`TYPE`
			FROM ". CounterTable::getTableName(). "
			WHERE
				USER_ID IN ({$this->userId}, 0)
		");
		$rows = $res->fetchAll();

		$this->updateState($rows);
	}

	/**
	 *
	 */
	private function updateRawCounters(): void
	{
		$this->counters = [];
		$counters = [];
		foreach ($this->state as $item)
		{
			if ($item['TYPE'] === CounterDictionary::COUNTER_FLAG_COUNTED)
			{
				continue;
			}
			if (in_array($item['TYPE'], CounterDictionary::MAP_EXPIRED))
			{
				$counters[CounterDictionary::COUNTER_EXPIRED][$item['GROUP_ID']][$item['TASK_ID']] = $item['VALUE'];
			}

			if (in_array($item['TYPE'], CounterDictionary::MAP_COMMENTS))
			{
				$counters[CounterDictionary::COUNTER_NEW_COMMENTS][$item['GROUP_ID']][$item['TASK_ID']] = $item['VALUE'];
			}

			$counters[$item['TYPE']][$item['GROUP_ID']][$item['TASK_ID']] = $item['VALUE'];
		}

		foreach ($counters as $type => $groups)
		{
			foreach ($groups as $group => $values)
			{
				$this->counters[$type][$group] = array_sum($values);
			}
		}
	}

	/**
	 * @param array $names
	 */
	private function updateUserCounters(): void
	{
		$value = $this->getValue(CounterDictionary::COUNTER_EXPIRED) + $this->getValue(CounterDictionary::COUNTER_NEW_COMMENTS);
		\CUserCounter::Set($this->userId, CounterDictionary::getCounterId(CounterDictionary::COUNTER_TOTAL), $value, '**', '', false);
	}
}