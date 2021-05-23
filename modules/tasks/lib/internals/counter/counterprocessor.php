<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter;


use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter\Exception\UnknownCounterException;
use Bitrix\Tasks\Internals\Task\MemberTable;

class CounterProcessor
{
	public const STEP_LIMIT = 2000;

	private $userId;
	private $collector;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 *
	 */
	public function recountAll(): void
	{
		$this->dropAll();
		$this->recount(CounterDictionary::COUNTER_EXPIRED);
		$this->recount(CounterDictionary::COUNTER_NEW_COMMENTS);
		$this->saveFlag();
	}

	/**
	 *
	 */
	public function readAll(int $groupId = 0, string $role = null): void
	{
		$groupIds = [];
		if ($groupId)
		{
			$groupIds[] = $groupId;
		}

		$counters = array_merge(
			array_values(CounterDictionary::MAP_COMMENTS),
			array_values(CounterDictionary::MAP_MUTED_COMMENTS)
		);

		if (in_array($role, MemberTable::possibleTypes()))
		{
			$this->crossTypeReset([CounterDictionary::MAP_COMMENTS[$role], CounterDictionary::MAP_MUTED_COMMENTS[$role]], $counters, $groupIds);
		}
		else
		{
			$this->reset($counters, [], $groupIds);
		}

		CounterState::getInstance($this->userId)->reload();
	}

	/**
	 * @param array $tasks
	 */
	public function deleteTasks(array $tasks): void
	{
		$taskIds = array_keys($tasks);

		$counters = array_merge(
			array_values(CounterDictionary::MAP_EXPIRED),
			array_values(CounterDictionary::MAP_MUTED_EXPIRED),
			array_values(CounterDictionary::MAP_COMMENTS),
			array_values(CounterDictionary::MAP_MUTED_COMMENTS)
		);

		$this->reset($counters, $taskIds);

		CounterState::getInstance($this->userId)->reload();
	}

	/**
	 * @throws \Bitrix\Main\DB\SqlQueryException
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
	 * @param string $counter
	 * @param array $taskIds
	 * @throws UnknownCounterException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function recount(string $counter, array $taskIds = []): void
	{
		if (!array_key_exists($counter, CounterDictionary::MAP_COUNTERS))
		{
			throw new UnknownCounterException();
		}

		$targetTasks = $taskIds;
		if (empty($targetTasks))
		{
			$targetTasks = $this->getUserTasks();
		}

		if (count($targetTasks) > self::STEP_LIMIT)
		{
			$chunks = array_chunk($targetTasks, self::STEP_LIMIT);

			/**
			 * the first one will be return for immediately counting
			 */
			$targetTasks = array_shift($chunks);

			foreach ($chunks as $i => $rows)
			{
				$this->addToQueue($counter, $rows);
			}

			(new CounterQueueAgent())->addAgent();
		}

		$counters = $this->getCollector()->recount($counter, $targetTasks);

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

		CounterState::getInstance($this->userId)->updateState($counters, $counterTypes, $taskIds);
	}

	/**
	 * @param string $counter
	 * @param array $taskIds
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	private function addToQueue(string $counter, array $taskIds)
	{
		CounterQueue::getInstance()->add($this->userId, $counter, $taskIds);
	}

	/**
	 *
	 */
	private function saveFlag(): void
	{
		if (!$this->userId)
		{
			return;
		}

		$sql = "
			INSERT INTO ". CounterTable::getTableName() ."
			(`USER_ID`, `TASK_ID`, `GROUP_ID`, `TYPE`, `VALUE`)
			VALUES ({$this->userId}, 0, 0, '". CounterDictionary::COUNTER_FLAG_COUNTED ."', 1)
		";
		Application::getConnection()->query($sql);
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
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
	 * @param array $types
	 * @param array $tasksIds
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function reset(array $types, array $tasksIds = [], array $groupIds = []): void
	{
		$where = "AND `TYPE` IN ('". implode("','", $types) ."')";

		if (!empty($tasksIds))
		{
			$where .= " AND TASK_ID IN (". implode(",", $tasksIds) .")";
		}

		if (!empty($groupIds))
		{
			$where .= " AND GROUP_ID IN (". implode(",", $groupIds) .")";
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
	 * Selects tasks by $types and reset all counters by $coverTypes for these tasks
	 *
	 * @param array $types
	 * @param array $coverTypes
	 */
	private function crossTypeReset(array $types, array $coverTypes, array $groupIds = []): void
	{
		$sql = "
			DELETE ts1.*
			FROM ". CounterTable::getTableName(). " ts1
			INNER JOIN b_tasks_scorer ts2
				ON ts2.TASK_ID = ts1.TASK_ID
				AND ts2.USER_ID = {$this->userId}
				AND ts2.`TYPE` IN ('". implode("','", $types) ."')
			WHERE
				ts1.USER_ID = {$this->userId}
				AND ts1.`TYPE` IN ('". implode("','", $coverTypes) ."')
		";

		if (!empty($groupIds))
		{
			$sql .= "AND ts1.GROUP_ID IN (". implode(",", $groupIds) .")";
		}

		Application::getConnection()->query($sql);
	}

	/**
	 * @param array $data
	 * @throws \Bitrix\Main\DB\SqlQueryException
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
}