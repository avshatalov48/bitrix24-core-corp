<?php
namespace Bitrix\ImOpenLines\Tools;

use \Bitrix\Main\Application;

use \Bitrix\ImOpenLines\Model\LockTable;

/**
 * Class Lock
 * @package Bitrix\ImOpenLines
 */
class Lock
{
	/** @var Lock */
	private static $instance = false;
	private $unigId = null;
	private $connection = null;
	private $sqlHelper = null;

	/**
	 * @return string
	 */
	protected static function generateUniqId()
	{
		return md5(getmypid() . time() . randString(5));
	}

	/**
	 * @return string
	 */
	protected function getUniqId()
	{
		return $this->unigId;
	}

	/**
	 * @return Lock
	 */
	public static function getInstance()
	{
		if (empty(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Lock constructor.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	private function __construct()
	{
		$this->unigId = self::generateUniqId();

		$this->connection = Application::getConnection();
		$this->sqlHelper = $this->connection->getSqlHelper();

		$this->connection->queryExecute('DELETE FROM ' . LockTable::getTableName() . ' WHERE LOCK_TIME < NOW()');
	}

	/**
	 * @param $name
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function isFree($name)
	{
		$result = false;
		$row = $this->connection->queryScalar('SELECT ID FROM ' . LockTable::getTableName() . ' WHERE ID=\'' . $this->sqlHelper->forSql($name, 255) . '\' AND LOCK_TIME >= NOW()');

		if($row == NULL)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $name
	 * @param int $time
	 * @return bool
	 */
	public function set($name, $time = 60)
	{
		$result = false;

		$row = $this->connection->query('SELECT ID, (LOCK_TIME >= NOW()) as BLOCK, PID
			FROM ' . LockTable::getTableName() . ' 
			WHERE ID=\'' . $this->sqlHelper->forSql($name, 255) . '\' FOR UPDATE;
		')->fetch();

		if($row == false)
		{
			try
			{
				$this->connection->queryExecute('INSERT INTO ' . LockTable::getTableName() . ' 
					SET ID=\'' . $this->sqlHelper->forSql($name, 255) . '\', 
					DATE_CREATE=NOW(), 
					LOCK_TIME=TIMESTAMPADD(SECOND, ' . $this->sqlHelper->forSql($time, 255) . ', NOW()), 
					PID=\'' . $this->sqlHelper->forSql($this->getUniqId(), 255) . '\';
				');

				$result = true;
			}
			catch (\Exception $e)
			{
				$result = false;
			}
		}
		elseif($row['BLOCK'] == 0 || $row['PID'] == $this->getUniqId())
		{
			$this->connection->queryExecute('UPDATE  ' . LockTable::getTableName() . ' 
					SET DATE_CREATE=NOW(), 
					LOCK_TIME=TIMESTAMPADD(SECOND, ' . $this->sqlHelper->forSql($time, 255) . ', NOW()), 
					PID=\'' . $this->sqlHelper->forSql($this->getUniqId(), 255) . '\'
					WHERE
					ID=\'' . $this->sqlHelper->forSql($name, 255) . '\';
				');

			$result = true;
		}

		$this->connection->queryExecute('COMMIT;');

		return $result;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function delete($name)
	{
		$this->connection->queryExecute('DELETE FROM ' . LockTable::getTableName() . ' WHERE ID=\'' . $this->sqlHelper->forSql($name, 255) . '\'');

		return true;
	}
}