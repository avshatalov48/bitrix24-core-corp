<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Processor;


use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter\Exception\UnknownCounterException;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Counter\Queue\Queue;
use Bitrix\Tasks\Internals\Counter\Queue\Agent;
use Bitrix\Tasks\Internals\Counter\Collector\UserCollector;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterState;
use Bitrix\Tasks\Internals\Counter\CounterTable;
use Bitrix\Tasks\Internals\Counter\CounterController;

class UserProcessor
{
	use CommandTrait;

	private $userId;
	private $collector;

	private $userTasks = null;

	private static $instances = [];

	public static function getInstance(int $userId)
	{
		if (!array_key_exists($userId, self::$instances))
		{
			self::$instances[$userId] = new self($userId);
		}

		return self::$instances[$userId];
	}

	private function __construct(int $userId)
	{
		$this->userId = $userId;
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

		$types = array_merge(
			array_values(CounterDictionary::MAP_COMMENTS),
			array_values(CounterDictionary::MAP_MUTED_COMMENTS),
		);

		$coverTypes = $types;
		$coverTypes[] = CounterDictionary::COUNTER_GROUP_COMMENTS;

		if (in_array($role, MemberTable::possibleTypes()))
		{
			$this->crossTypeReset([CounterDictionary::MAP_COMMENTS[$role], CounterDictionary::MAP_MUTED_COMMENTS[$role]], $coverTypes, $groupIds);
		}
		else
		{
			$this->crossTypeReset($types, $coverTypes, $groupIds);
		}

		CounterState::reload($this->userId);
	}

	/**
	 * @param string $counter
	 * @param array $taskIds
	 * @throws UnknownCounterException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function recount(string $counter, array $taskIds = []): void
	{
		if (!in_array($counter, [CounterDictionary::COUNTER_EXPIRED, CounterDictionary::COUNTER_NEW_COMMENTS]))
		{
			throw new UnknownCounterException();
		}

		$targetTasks = $taskIds;
		if (empty($targetTasks))
		{
			$targetTasks = $this->getUserTasks();
		}

		if (count($targetTasks) > CounterController::STEP_LIMIT)
		{
			$chunks = array_chunk($targetTasks, CounterController::STEP_LIMIT);

			/**
			 * the first one will be return for immediately counting
			 */
			$targetTasks = array_shift($chunks);

			foreach ($chunks as $i => $rows)
			{
				$this->addToQueue($counter, $rows);
			}

			(new Agent())->addAgent();
		}

		$counters = $this->getCollector()->recount($counter, $targetTasks);

		if ($counter === CounterDictionary::COUNTER_EXPIRED)
		{
			$counterTypes = array_merge(array_values(CounterDictionary::MAP_EXPIRED), CounterDictionary::MAP_MUTED_EXPIRED);
		}
		else if ($counter === CounterDictionary::COUNTER_NEW_COMMENTS)
		{
			$counterTypes = array_merge(array_values(CounterDictionary::MAP_COMMENTS), CounterDictionary::MAP_MUTED_COMMENTS);
		}

		self::reset($this->userId, $counterTypes, $taskIds);
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
		Queue::getInstance()->add($this->userId, $counter, $taskIds);
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function getUserTasks(): array
	{
		if ($this->userTasks === null)
		{
			$sql = "
				SELECT DISTINCT(TASK_ID)
				FROM `b_tasks_member`
				WHERE USER_ID = {$this->userId}
			";
			$res = Application::getConnection()->query($sql);
			$this->userTasks = [];
			while ($row = $res->fetch())
			{
				$this->userTasks[] = $row['TASK_ID'];
			}
		}

		return $this->userTasks;
	}

	/**
	 * @return UserCollector
	 */
	private function getCollector(): UserCollector
	{
		if (!$this->collector)
		{
			$this->collector = UserCollector::getInstance($this->userId);
		}
		return $this->collector;
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


}