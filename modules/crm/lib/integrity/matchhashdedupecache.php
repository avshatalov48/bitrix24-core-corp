<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\DbHelper;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;

class MatchHashDedupeCache
{
	protected ?string $tableName = null;
	protected ?string $tableMask = null;
	protected ?string $entityName = null;
	protected ?string $currentTableName = null;
	protected ?bool $isExists = null;

	protected MatchHashDedupeQueryParams $params;

	public function __construct(MatchHashDedupeQueryParams $params)
	{
		$this->params = $params;
	}

	public static function isEnabled(): bool
	{
		return (Option::get('crm', '~enable_duplicate_table_cache', 'N') === 'Y'); // temporary disabled by default due to infrastructure overload
	}

	public static function dropExpired(): void
	{
		$cache = new static(new MatchHashDedupeQueryParams('', 0, 0, 0));
		$tableNames = $cache->getTableNamesLike($cache->getTableNamePrefix() . '_%');

		foreach ($tableNames as $tableName)
		{
			if ($cache->isTableExpired($tableName))
			{
				Application::getInstance()->getConnection()->dropTable($tableName);
			}
		}
	}

	protected static function getTableTimeUntilOverdue(): int
	{
		return 3600;
	}

	protected static function getTableTimeToDie(): int
	{
		return (3600 * 24 * 2);
	}

	protected static function getTableMaxLifeTime(): int
	{
		return static::getTableTimeToDie() - static::getTableTimeUntilOverdue();
	}

	protected function getTableNamePrefix(): string
	{
		return 'b_crm_dp_mhdc';
	}

	protected function getEntityNamePrefix(): string
	{
		return 'DedupeCache';
	}

	protected function getTableNamePostfix(): string
	{
		return date('YmdHis');
	}

	protected function getTableNameSuffix(): string
	{
		return $this->params->getHash();
	}

	protected function refreshTableInfo(): void
	{
		$tableNamePart = $this->getTableNamePrefix() . '_' . $this->getTableNameSuffix() . '_';

		$this->tableMask = $tableNamePart . '%';

		$this->tableName = $tableNamePart . $this->getTableNamePostfix();
	}

	protected function getNewTableName(): string
	{
		if ($this->tableName === null)
		{
			$this->refreshTableInfo();
		}

		return $this->tableName;
	}

	protected function getTableMask(): string
	{
		if ($this->tableMask === null)
		{
			$this->refreshTableInfo();
		}

		return $this->tableMask;
	}

	protected function getEntityClassName(): string
	{
		if ($this->entityName === null)
		{
			$this->entityName =
				$this->getEntityNamePrefix()
				. $this->getTableNameSuffix()
			;
		}

		return $this->entityName;
	}

	protected function getFullEntityClassName(): string
	{
		return '\\' . __NAMESPACE__ . '\\' . $this->getEntityClassName();
	}

	protected function getTableNamesLikeResource(string $mask): \Bitrix\Main\DB\Result
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$maskSql = strtolower($sqlHelper->ForSql($mask));

		return DbHelper::queryByDbType(
			"SHOW TABLES LIKE '$maskSql'",
			"SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename LIKE '$maskSql'"
		);
	}

	protected function getTableNamesLike(string $mask): array
	{
		$result = [];

		$res = $this->getTableNamesLikeResource($mask);
		while ($row = $res->fetch())
		{
			$result[] = current($row);
		}

		return $result;
	}

	protected function createTable(Query $baseQuery): Result
	{
		$result = new Result();

		$connection = \Bitrix\Main\Application::getConnection();

		$tableName = $this->getNewTableName();
		$subQuery = $baseQuery->getQuery();

		$connection->startTransaction();

		DbHelper::queryByDbType(
			"CREATE TABLE IF NOT EXISTS $tableName ("
			. "ID INT(1) UNSIGNED NOT NULL, "
			. "MATCH_HASH VARCHAR(32) NOT NULL, "
			. "QTY INT(1) UNSIGNED NOT NULL, "
			. "PRIMARY KEY (ID)"
			. ")",
			"CREATE TABLE IF NOT EXISTS $tableName ("
			. "ID int8 NOT NULL, "
			. "MATCH_HASH varchar(32) NOT NULL, "
			. "QTY int8 NOT NULL, "
			. "PRIMARY KEY (ID)"
			. ")"
		);

		if (\Bitrix\Crm\DbHelper::isPgSqlDb())
		{
			$sequenceName = "{$tableName}_sq";
			$connection->queryExecute("CREATE SEQUENCE $sequenceName AS int8");
			$query =
				/** @lang PostgreSQL */
				"INSERT INTO $tableName (ID, MATCH_HASH, QTY) " . PHP_EOL
				. "SELECT" . PHP_EOL
				. "  nextval('$sequenceName') AS ID," . PHP_EOL
				. "  T1.MATCH_HASH," . PHP_EOL
				. "  T1.QTY" . PHP_EOL
				. "FROM (" . PHP_EOL
				. "  $subQuery" . PHP_EOL
				. ") T1"
			;
			$connection->queryExecute($query);
			$connection->queryExecute("DROP SEQUENCE $sequenceName;");
			unset($sequenceName);
		}
		else    // MYSQL
		{
			$varName = "@${tableName}_rn";
			$connection->queryExecute("SET $varName = 0");
			$query =
				/** @lang MySQL */
				"INSERT INTO $tableName (ID, MATCH_HASH, QTY) " . PHP_EOL
				. "SELECT" . PHP_EOL
				. "  ($varName := $varName + 1) AS ID," . PHP_EOL
				. "  T1.MATCH_HASH," . PHP_EOL
				. "  T1.QTY" . PHP_EOL
				. "FROM (" . PHP_EOL
				. "  $subQuery" . PHP_EOL
				. ") T1"
			;
			$connection->queryExecute($query);
			unset($varName);
		}

		$this->isExists = null;

		if ($this->isExists())
		{
			$this->currentTableName = $tableName;
			$connection->createIndex(
				$tableName,
				'UX_' . $tableName. '_1',
				'MATCH_HASH',
				null,
				Connection::INDEX_UNIQUE
			);
		}

		$connection->commitTransaction();

		return $result;
	}

	protected function getCurrentTableName(): string
	{
		if ($this->currentTableName === null)
		{
			$tableNames = $this->getTableNamesLike($this->getTableMask());
			rsort($tableNames);
			$this->currentTableName = empty($tableNames) ? '' : $tableNames[0];
		}

		return $this->currentTableName;
	}

	protected function getTableTimestamp(string $tableName): false|int
	{
		$matches = [];
		$tablePrefix = preg_quote($this->getTableNamePrefix());
		if (
			$tableName !== ''
			&& preg_match("/^{$tablePrefix}_[0-9a-f]{16}_([0-9]{14})$/", $tableName, $matches)
		)
		{
			return strtotime($matches[1]);
		}

		return false;
	}

	protected function checkTableTime(string $tableName): bool
	{
		$tableTime = $this->getTableTimestamp($tableName);
		if ($tableTime !== false)
		{
			$tableLifeTime = time() - $tableTime;
			if ($tableLifeTime <= static::getTableMaxLifeTime())
			{
				return true;
			}
		}

		return false;
	}

	protected function isTableExpired(string $tableName): bool
	{
		$tableTime = $this->getTableTimestamp($tableName);
		if ($tableTime !== false)
		{
			$tableLifeTime = time() - $tableTime;
			if ($tableLifeTime > static::getTableTimeToDie())
			{
				return true;
			}
		}

		return false;
	}

	public function isExists(): bool
	{
		if ($this->isExists === null)
		{
			$res = $this->getTableNamesLikeResource($this->getTableMask());
			$row = $res->fetch();
			$this->isExists = is_array($row) && !empty($row) && $this->checkTableTime(current($row));
		}

		return $this->isExists;
	}

	public function create(Query $baseQuery): Result
	{
		$result = new Result();

		if (!$this->isExists())
		{
			return $this->createTable($baseQuery);
		}

		return $result;
	}

	public function drop(): void
	{
		$tableNames = $this->getTableNamesLike($this->getTableMask());

		foreach ($tableNames as $tableName)
		{
			Application::getConnection()->dropTable($tableName);
		}

		$this->currentTableName = null;
		$this->isExists = null;
	}

	protected function makeQuery(): Result
	{
		$result = new Result();

		$fullEntityClassName = $this->getFullEntityClassName();
		if (Entity::isExists($fullEntityClassName))
		{
			// rebuild if it already exists
			Entity::destroy($fullEntityClassName);
			$entity = Entity::getInstance($fullEntityClassName);
		}
		else
		{
			$entity = Entity::compileEntity(
				$this->getEntityClassName(),
				[],
				[
					'table_name' => $this->getCurrentTableName(),
					'parent' => MatchHashDedupeCacheTable::class,
					'namespace' => __NAMESPACE__,
				]
			);
		}

		$query = new Query($entity);
		$query->setSelect(['MATCH_HASH', 'QTY'])->setOrder('MATCH_HASH');

		$result->setData(['query' => $query]);

		return $result;
	}

	public function getQuery(): Result
	{
		if ($this->isExists())
		{
			return $this->makeQuery();
		}

		$result = new Result();
		$result->addError(new Error('Cache table is not exists.'));

		return $result;
	}
}
