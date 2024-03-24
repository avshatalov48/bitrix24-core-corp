<?php

namespace Bitrix\Tasks\Internals\Counter\Queue;

use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter\Exception\CounterQueuePopException;

class Queue
{
	private $popped = [];
	private static $instance;
	private static $inQueue = [];

	/**
	 * @param int $userId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function isInQueue(int $userId): bool
	{
		if (!array_key_exists($userId, self::$inQueue))
		{
			$res = QueueTable::getRow([
				'filter' => [
					'=USER_ID' => $userId
				]
			]);

			self::$inQueue[$userId] = (bool) $res;
		}

		return self::$inQueue[$userId];
	}

	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * CounterQueue constructor.
	 */
	private function __construct()
	{

	}

	/**
	 * @param int $userId
	 * @param string $type
	 * @param array $tasks
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function add(int $userId, string $type, array $tasks): void
	{
		$req = [];
		foreach ($tasks as $taskId)
		{
			$req[] = $userId .',\''. $type .'\',' . (int) $taskId;
		}

		$sql = "
			INSERT INTO ". QueueTable::getTableName(). "
			(USER_ID, TYPE, TASK_ID)
			VALUES
			(". implode("),(", $req) .")
		";

		Application::getConnection()->query($sql);

		self::$inQueue[$userId] = true;
	}

	/**
	 * @param int $limit
	 * @return array
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function get(int $limit): array
	{
		if (!empty($this->popped))
		{
			throw new CounterQueuePopException();
		}

		$sql = "
			SELECT 
				ID,
				USER_ID, 
				TYPE,
				TASK_ID
			FROM `". QueueTable::getTableName() ."`
			ORDER BY ID ASC
			LIMIT {$limit}
		";

		$res = Application::getConnection()->query($sql);

		$queue = [];
		while ($row = $res->fetch())
		{
			$this->popped[] = $row['ID'];

			$userId = (int) $row['USER_ID'];
			$type = $row['TYPE'];
			$key = $userId.'_'.$type;

			if (!array_key_exists($key, $queue))
			{
				$queue[$userId.'_'.$type] = [
					'USER_ID' => $userId,
					'TYPE' => $type
				];
			}
			$queue[$key]['TASKS'][] = (int) $row['TASK_ID'];
		}

		return $queue;
	}

	/**
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function done(): void
	{
		if (empty($this->popped))
		{
			return;
		}

		$sql = "
			DELETE
			FROM `". QueueTable::getTableName() ."`
			WHERE ID IN (". implode(",", $this->popped) .")
		";
		Application::getConnection()->query($sql);

		$this->popped = [];
	}
}