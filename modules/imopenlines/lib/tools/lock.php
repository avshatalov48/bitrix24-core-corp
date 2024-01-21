<?php
namespace Bitrix\ImOpenLines\Tools;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlException;
use Bitrix\ImOpenLines\Model\LockTable;

class Lock
{
	/** @var self */
	private static $instance = null;
	private $unigId = null;
	private $connection = null;
	private $sqlHelper = null;
	private $table = null;

	/**
	 * @return self
	 */
	public static function getInstance(): self
	{
		if (empty(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Lock constructor.
	 */
	private function __construct()
	{
		$this->unigId = self::generateUniqId();

		$this->connection = Application::getConnection();
		$this->sqlHelper = $this->connection->getSqlHelper();
		$this->table = LockTable::getTableName();

		$now = $this->sqlHelper->getCurrentDateTimeFunction();
		$this->connection->queryExecute("DELETE FROM {$this->table} WHERE LOCK_TIME < {$now}");
	}

	/**
	 * @return string
	 */
	protected static function generateUniqId(): string
	{
		return md5(getmypid() . time() . randString(5));
	}

	/**
	 * @return string
	 */
	protected function getUniqId(): string
	{
		return $this->unigId;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isFree(string $name): bool
	{
		$name = $this->sqlHelper->forSql($name, 255);
		$now = $this->sqlHelper->getCurrentDateTimeFunction();

		$row = $this->connection->queryScalar("SELECT ID FROM {$this->table} WHERE ID = '{$name}' AND LOCK_TIME >= {$now}");

		return ($row === null);
	}

	/**
	 * @param string $name
	 * @param int $time
	 * @return bool
	 */
	public function set(string $name, int $time = 60): bool
	{
		$result = false;

		$name = $this->sqlHelper->forSql($name, 255);
		$uniqId = $this->sqlHelper->forSql($this->getUniqId(), 255);
		$now = $this->sqlHelper->getCurrentDateTimeFunction();
		$lockTime = $this->sqlHelper->addSecondsToDateTime($time, $now);

		$this->connection->startTransaction();

		try
		{
			$sql = "
				SELECT ID, PID, (CASE WHEN LOCK_TIME >= {$now} THEN 1 ELSE 0 END) as BLOCK 
				FROM {$this->table} WHERE ID = '{$name}' 
				FOR UPDATE
			";
			$res = $this->connection->query($sql);
			$row = $res->fetch();

			if ($row == false)
			{
				$this->connection->queryExecute("
					INSERT INTO {$this->table} (ID, DATE_CREATE, LOCK_TIME, PID) 
					VALUES ('{$name}', {$now}, {$lockTime}, '{$uniqId}')
				");

				$result = true;
			}
			elseif ($row['BLOCK'] == 0 || $row['PID'] == $this->getUniqId())
			{
				$this->connection->queryExecute("
					UPDATE {$this->table} 
					SET 
						DATE_CREATE = {$now}, 
						LOCK_TIME = {$lockTime}, 
						PID = '{$uniqId}'
					WHERE
						ID = '{$name}'
				");

				$result = true;
			}

			$this->connection->commitTransaction();
		}
		catch (SqlException $exception)
		{
			$this->connection->rollbackTransaction();
		}

		return $result;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function delete(string $name): bool
	{
		$name = $this->sqlHelper->forSql($name, 255);
		$this->connection->queryExecute("DELETE FROM {$this->table} WHERE ID = '{$name}'");

		return true;
	}
}