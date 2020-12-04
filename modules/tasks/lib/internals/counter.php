<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter\CounterCollector;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterQueue;
use Bitrix\Tasks\Internals\Counter\CounterQueueAgent;
use Bitrix\Tasks\Internals\Counter\CounterTable;
use Bitrix\Tasks\Internals\Counter\Exception\UnknownCounterException;
use Bitrix\Tasks\Util\User;
use CTasks;
use CUserCounter;
use Bitrix\Tasks\Util\Collection;

/**
 * Class Counter
 *
 * @package Bitrix\Tasks\Internals
 */
class Counter
{
	public const STEP_LIMIT = 2000;

	private static $instance;

	private $userId;
	private $state = [];
	private $counters = [];

	private $collector;

	/**
	 * @param $userId
	 * @return static
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getInstance($userId): self
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
	 * Counter constructor.
	 *
	 * @param $userId
	 * @param int $groupId
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function __construct($userId)
	{
		$this->userId = (int)$userId;

		$this->loadCounters();

		if (!$this->isCounted())
		{
			if (!empty($this->state))
			{
				$this->dropAll();
			}
			$this->recountAll();
		}
	}

	/**
	 * @return array
	 */
	public function getRawCounters(): array
	{
		return $this->counters;
	}

	/**
	 * @param Collection $counterNamesCollection
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 *
	 * Kept due to backward compatibility
	 */
	public function processRecalculate(Collection $counterNamesCollection): void
	{

	}

	/**
	 * @param $role
	 * @param array $params
	 * @return array|array[]
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getCounters($role, int $groupId = 0, $params = []): array
	{
		$skipAccessCheck = (isset($params['SKIP_ACCESS_CHECK']) && $params['SKIP_ACCESS_CHECK']);

		if (!$skipAccessCheck && !$this->isAccessToCounters())
		{
			return [];
		}

		switch (strtolower($role))
		{
			case Counter\Role::ALL:
				$counters = [
					'total' => [
						'counter' => $this->get(CounterDictionary::COUNTER_TOTAL, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_NEW_COMMENTS, $groupId),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::RESPONSIBLE:
				$counters = [
					'total' => [
						'counter' => $this->get(CounterDictionary::COUNTER_MY, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_MY_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_MY_NEW_COMMENTS, $groupId),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::ORIGINATOR:
				$counters = [
					'total' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ORIGINATOR, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ORIGINATOR_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS, $groupId),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::ACCOMPLICE:
				$counters = [
					'total' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ACCOMPLICES, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ACCOMPLICES_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS, $groupId),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			case Counter\Role::AUDITOR:
				$counters = [
					'total' => [
						'counter' => $this->get(CounterDictionary::COUNTER_AUDITOR, $groupId),
						'code' => '',
					],
					'expired' => [
						'counter' => $this->get(CounterDictionary::COUNTER_AUDITOR_EXPIRED, $groupId),
						'code' => Counter\Type::TYPE_EXPIRED,
					],
					'new_comments' => [
						'counter' => $this->get(CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS, $groupId),
						'code' => Counter\Type::TYPE_NEW_COMMENTS,
					],
				];
				break;

			default:
				$counters = [];
				break;
		}

		return $counters;
	}

	/**
	 * @param $name
	 * @return bool|int|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function get($name, int $groupId = 0)
	{
		switch ($name)
		{
			case CounterDictionary::COUNTER_TOTAL:
				$value = $this->get(CounterDictionary::COUNTER_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_MY:
				$value = $this->get(CounterDictionary::COUNTER_MY_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_MY_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_ORIGINATOR:
				$value = $this->get(CounterDictionary::COUNTER_ORIGINATOR_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_ACCOMPLICES:
				$value = $this->get(CounterDictionary::COUNTER_ACCOMPLICES_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_AUDITOR:
				$value = $this->get(CounterDictionary::COUNTER_AUDITOR_EXPIRED, $groupId)
					+ $this->get(CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS, $groupId);
				break;

			case CounterDictionary::COUNTER_EFFECTIVE:
				$value = $this->getKpi();
				break;

			default:
				$value = $this->getInternal($name, $groupId);
				break;
		}

		return $value;
	}

	/**
	 * @param array $taskIds
	 * @return array
	 */
	public function getCommentsCount(array $taskIds): array
	{
		$res = array_fill_keys($taskIds, 0);

		foreach ($this->state as $row)
		{
			if (!in_array($row['TASK_ID'], $taskIds))
			{
				continue;
			}
			if (
				in_array($row['TYPE'], CounterDictionary::MAP_COMMENTS)
				|| in_array($row['TYPE'], CounterDictionary::MAP_MUTED_COMMENTS)
			)
			{
				$res[$row['TASK_ID']] = $row['VALUE'];
			}
		}

		return $res;
	}

	/**
	 * @throws Main\DB\SqlQueryException
	 */
	public function readAll(): void
	{
		$this->reset(CounterDictionary::MAP_COMMENTS);
		$this->reset(CounterDictionary::MAP_MUTED_COMMENTS);
		$this->loadCounters();
	}

	/**
	 * @param array $tasks
	 * @throws Main\DB\SqlQueryException
	 */
	public function deleteTasks(array $tasks): void
	{
		$this->reset(CounterDictionary::MAP_EXPIRED, $tasks);
		$this->reset(CounterDictionary::MAP_MUTED_EXPIRED, $tasks);
		$this->reset(CounterDictionary::MAP_COMMENTS, $tasks);
		$this->reset(CounterDictionary::MAP_MUTED_COMMENTS, $tasks);
		$this->loadCounters();
	}

	/**
	 * @param array $taskIds
	 * @throws Main\Db\SqlQueryException
	 */
	public function recount(string $counter, array $taskIds = []): void
	{
		if (!array_key_exists($counter, CounterDictionary::MAP_COUNTERS))
		{
			throw new UnknownCounterException();
		}

		if (empty($taskIds))
		{
			$taskIds = $this->getUserTasks();
		}

		if (count($taskIds) > self::STEP_LIMIT)
		{
			$chunks = array_chunk($taskIds, self::STEP_LIMIT);

			/**
			 * the first one will be return for immediately counting
			 */
			$taskIds = array_shift($chunks);

			foreach ($chunks as $i => $rows)
			{
				$this->addToQueue($counter, $rows);
			}

			(new CounterQueueAgent())->addAgent();
		}

		$counters = $this->getCollector()->recount($counter, $taskIds);

		$counterTypes = CounterDictionary::MAP_COUNTERS[$counter];
		if ($counter === CounterDictionary::COUNTER_EXPIRED)
		{
			$counterTypes = array_merge(array_values(CounterDictionary::MAP_EXPIRED), CounterDictionary::MAP_MUTED_EXPIRED);
		}
		else if ($counter === CounterDictionary::COUNTER_NEW_COMMENTS)
		{
			$counterTypes = array_merge(array_values(CounterDictionary::MAP_COMMENTS), CounterDictionary::MAP_MUTED_COMMENTS);
		}

		$this->reset($counterTypes, $taskIds);
		$this->batchInsert($counters);
		$this->updateState($counters, $counterTypes, $taskIds);
	}

	/**
	 * @param string $counter
	 * @param array $taskIds
	 * @throws Main\Db\SqlQueryException
	 */
	private function addToQueue(string $counter, array $taskIds)
	{
		CounterQueue::getInstance()->add($this->userId, $counter, $taskIds);
	}

	/**
	 *
	 */
	private function recountAll(): void
	{
		$this->recount(CounterDictionary::COUNTER_EXPIRED);
		$this->recount(CounterDictionary::COUNTER_NEW_COMMENTS);
		$this->saveMark();
	}

	/**
	 * @throws Main\Db\SqlQueryException
	 */
	private function dropAll(): void
	{
		$sql = "
			DELETE FROM ". CounterTable::getTableName() ."
			WHERE `USER_ID` = {$this->userId}
		";
		Application::getConnection()->query($sql);
	}

	/**
	 *
	 */
	private function saveMark(): void
	{
		$sql = "
			INSERT INTO ". CounterTable::getTableName() ."
			(`USER_ID`, `TASK_ID`, `GROUP_ID`, `TYPE`, `VALUE`)
			VALUES ({$this->userId}, 0, 0, '". CounterDictionary::COUNTER_FLAG_COUNTED ."', 1)
		";
		Application::getConnection()->query($sql);
	}


	/**
	 * @param string $name
	 * @param int|null $groupId
	 * @return int
	 */
	private function getInternal(string $name, int $groupId = null): int
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
	 * @return bool
	 */
	private function isAccessToCounters(): bool
	{
		return $this->userId === User::getId()
			|| User::isSuper()
			|| CTasks::IsSubordinate($this->userId, User::getId());
	}

	/**
	 * @return bool|int
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getKpi()
	{
		$efficiency = Effective::getEfficiencyFromUserCounter($this->userId);
		if (!$efficiency && ($efficiency = Effective::getAverageEfficiency(null, null, $this->userId)))
		{
			Effective::setEfficiencyToUserCounter($this->userId, $efficiency);
		}

		return $efficiency;
	}

	/**
	 * @throws Main\DB\SqlQueryException
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
				USER_ID = {$this->userId}
		");
		$rows = $res->fetchAll();

		$this->updateState($rows);
	}

	/**
	 * @param array $rawCounters
	 */
	private function updateState(array $rawCounters, array $types = [], array $taskIds = []): void
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
		$this->updateUserCounters([CounterDictionary::COUNTER_TOTAL]);
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
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function updateUserCounters(array $names): void
	{
		foreach ($names as $name)
		{
			CUserCounter::Set($this->userId, Counter\CounterDictionary::getCounterId($name), $this->get($name), '**', '', false);
		}
	}

	/**
	 * @return CounterCollector
	 */
	private function getCollector(): CounterCollector
	{
		if (!$this->collector)
		{
			$this->collector = new CounterCollector($this->userId);
		}
		return $this->collector;
	}

	/**
	 * @param array $data
	 * @param array $clearTypes
	 * @throws Main\Db\SqlQueryException
	 */
	private function batchInsert(array $data): void
	{
		$req = [];
		foreach ($data as $row)
		{
			$row['TYPE'] = "'". $row['TYPE'] ."'";
			$req[] = implode(',', $row);
		}

		if (empty($req))
		{
			return;
		}

		$sql = "
			INSERT INTO ". CounterTable::getTableName(). "
			(`USER_ID`, `TASK_ID`, `GROUP_ID`, `TYPE`, `VALUE`)
			VALUES
			(". implode("),(", $req) .")
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @param array $types
	 * @param array $tasksIds
	 * @throws Main\Db\SqlQueryException
	 */
	private function reset(array $types, array $tasksIds = []): void
	{
		$where = "AND `TYPE` IN ('". implode("','", $types) ."')";

		if (!empty($tasksIds))
		{
			$where .= " AND TASK_ID IN (". implode(",", $tasksIds) .")";
		}

		$sql = "
			DELETE
			FROM ". CounterTable::getTableName(). "
			WHERE
				`USER_ID` = {$this->userId}
				{$where}
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @return array
	 * @throws Main\Db\SqlQueryException
	 */
	private function getUserTasks(): array
	{
		$sql = "
			SELECT DISTINCT(TASK_ID)
			FROM `b_tasks_member`
			WHERE USER_ID = {$this->userId}
		";
		$res = Application::getConnection()->query($sql);
		$ids = [];
		while ($row = $res->fetch())
		{
			$ids[] = $row['TASK_ID'];
		}
		return $ids;
	}

	/**
	 * @return bool
	 */
	private function isCounted(): bool
	{
		if (empty($this->state))
		{
			return false;
		}

		foreach ($this->state as $row)
		{
			if ($row['TYPE'] === CounterDictionary::COUNTER_FLAG_COUNTED)
			{
				return true;
			}
		}

		return false;
	}
}