<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Fields\ScalarField;

/**
 * Class OracleConnection
 *
 * Class for Oracle database connections.
 * @package Bitrix\Main\DB
 */
class OracleConnection extends Connection
{
	private $transaction = OCI_COMMIT_ON_SUCCESS;

	protected $lastInsertedId;

	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return \Bitrix\Main\Db\SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new OracleSqlHelper($this);
	}

	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	/**
	 * Establishes a connection to the database.
	 * Includes php_interface/after_connect_d7.php on success.
	 * Throws exception on failure.
	 *
	 * @return void
	 * @throws \Bitrix\Main\DB\ConnectionException
	 */
	protected function connectInternal()
	{
		if ($this->isConnected)
			return;

		if (($this->options & self::PERSISTENT) != 0)
			$connection = oci_pconnect($this->login, $this->password, $this->database);
		else
			$connection = oci_new_connect($this->login, $this->password, $this->database);

		if (!$connection)
			throw new ConnectionException('Oracle connect error', $this->getErrorMessage());

		$this->isConnected = true;
		$this->resource = $connection;

		$this->afterConnected();
	}

	/**
	 * Disconnects from the database.
	 * Does nothing if there was no connection established.
	 *
	 * @return void
	 */
	protected function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		$this->isConnected = false;
		oci_close($this->resource);
	}

	/*********************************************************
	 * Query
	 *********************************************************/

	/**
	 * Executes a query against connected database.
	 * Rises SqlQueryException on any database error.
	 * <p>
	 * When object $trackerQuery passed then calls its startQuery and finishQuery
	 * methods before and after query execution.
	 *
	 * @param string                            $sql Sql query.
	 * @param array                             $binds Array of binds.
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery Debug collector object.
	 *
	 * @return resource
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	protected function queryInternal($sql, array $binds = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->connectInternal();

		if ($trackerQuery != null)
			$trackerQuery->startQuery($sql, $binds);

		$result = oci_parse($this->resource, $sql);

		if (!$result)
		{
			if ($trackerQuery != null)
				$trackerQuery->finishQuery();

			throw new SqlQueryException("", $this->getErrorMessage($this->resource), $sql);
		}

		$executionMode = $this->transaction;

		/** @var \OCI_Lob[] $clob */
		$clob = array();

		if (!empty($binds))
		{
			$executionMode = OCI_DEFAULT;
			foreach ($binds as $key => $val)
			{
				$clob[$key] = oci_new_descriptor($this->resource, OCI_DTYPE_LOB);
				oci_bind_by_name($result, ":".$key, $clob[$key], -1, OCI_B_CLOB);
			}
		}

		if (!oci_execute($result, $executionMode))
		{
			if ($trackerQuery != null)
			{
				$trackerQuery->finishQuery();
			}

			throw new SqlQueryException("", $this->getErrorMessage($result), $sql);
		}

		if (!empty($binds))
		{
			if (oci_num_rows($result) > 0)
			{
				foreach ($binds as $key => $val)
				{
					if($clob[$key])
					{
						$clob[$key]->save($binds[$key]);
					}
				}
			}

			if ($this->transaction == OCI_COMMIT_ON_SUCCESS)
			{
				oci_commit($this->resource);
			}

			foreach ($binds as $key => $val)
			{
				if($clob[$key])
				{
					$clob[$key]->free();
				}
			}
		}

		if ($trackerQuery != null)
		{
			$trackerQuery->finishQuery();
		}

		$this->lastQueryResult = $result;

		return $result;
	}

	/**
	 * Returns database depended result of the query.
	 *
	 * @param resource $result Result of internal query function.
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery Debug collector object.
	 *
	 * @return Result
	 */
	protected function createResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new OracleResult($result, $this, $trackerQuery);
	}

	/**
	 * Executes a query to the database.
	 *
	 * - query($sql)
	 * - query($sql, $limit)
	 * - query($sql, $offset, $limit)
	 * - query($sql, $binds)
	 * - query($sql, $binds, $limit)
	 * - query($sql, $binds, $offset, $limit)
	 *
	 * @param string $sql Sql query.
	 * @param array $binds Array of binds.
	 * @param int $offset Offset of the first row to return, starting from 0.
	 * @param int $limit Limit rows count.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function query($sql)
	{
		list($sql, $binds, $offset, $limit) = self::parseQueryFunctionArgs(func_get_args());

		if (!empty($binds))
		{
			$binds1 = $binds2 = "";
			foreach ($binds as $key => $value)
			{
				if ($value <> '')
				{
					if ($binds1 != "")
					{
						$binds1 .= ",";
						$binds2 .= ",";
					}

					$binds1 .= $key;
					$binds2 .= ":".$key;
				}
			}

			if ($binds1 != "")
				$sql .= " RETURNING ".$binds1." INTO ".$binds2;
		}

		return parent::query($sql, $binds, $offset, $limit);
	}

	/**
	 * Adds row to table and returns ID of the added row.
	 * <p>
	 * $identity parameter must be null when table does not have autoincrement column.
	 *
	 * @param string $tableName Name of the table for insertion of new row..
	 * @param array $data Array of columnName => Value pairs.
	 * @param string $identity For Oracle only.
	 *
	 * @return integer
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function add($tableName, array $data, $identity = "ID")
	{
		if($identity !== null && !isset($data[$identity]))
			$data[$identity] = $this->getNextId("sq_".$tableName);

		$insert = $this->getSqlHelper()->prepareInsert($tableName, $data);

		$binds = $insert[2];

		$sql =
			"INSERT INTO ".$tableName."(".$insert[0].") ".
			"VALUES (".$insert[1].")";

		$this->queryExecute($sql, $binds);

		$this->lastInsertedId = $data[$identity];

		return $data[$identity];
	}

	/**
	 * Gets next value from the database sequence.
	 * <p>
	 * Sequence name may contain only A-Z,a-z,0-9 and _ characters.
	 *
	 * @param string $name Name of the sequence.
	 *
	 * @return null|string
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function getNextId($name = "")
	{
		$name = preg_replace("/[^A-Za-z0-9_]+/i", "", $name);
		$name = trim($name);

		if($name == '')
			throw new \Bitrix\Main\ArgumentNullException("name");

		$sql = "SELECT ".$this->getSqlHelper()->quote($name).".NEXTVAL FROM DUAL";

		$result = $this->query($sql);
		if ($row = $result->fetch())
		{
			return array_shift($row);
		}

		return null;
	}

	/**
	 * @return integer
	 */
	public function getInsertedId()
	{
		return $this->lastInsertedId;
	}

	/**
	 * Returns affected rows count from last executed query.
	 *
	 * @return integer
	 */
	public function getAffectedRowsCount()
	{
		return oci_num_rows($this->lastQueryResult);
	}

	/**
	 * Checks if a table exists.
	 *
	 * @param string $tableName The table name.
	 *
	 * @return boolean
	 */
	public function isTableExists($tableName)
	{
		if (empty($tableName))
			return false;

		$result = $this->queryScalar("
			SELECT COUNT(TABLE_NAME)
			FROM USER_TABLES
			WHERE TABLE_NAME LIKE UPPER('".$this->getSqlHelper()->forSql($tableName)."')
		");
		return ($result > 0);
	}

	/**
	 * Checks if an index exists.
	 * Actual columns in the index may differ from requested.
	 * $columns may present an "prefix" of actual index columns.
	 *
	 * @param string $tableName A table name.
	 * @param array  $columns An array of columns in the index.
	 *
	 * @return boolean
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function isIndexExists($tableName, array $columns)
	{
		return $this->getIndexName($tableName, $columns) !== null;
	}

	/**
	 * Returns the name of an index.
	 *
	 * @param string $tableName A table name.
	 * @param array $columns An array of columns in the index.
	 * @param bool $strict The flag indicating that the columns in the index must exactly match the columns in the $arColumns parameter.
	 *
	 * @return string|null Name of the index or null if the index doesn't exist.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function getIndexName($tableName, array $columns, $strict = false)
	{
		if (!is_array($columns) || empty($columns))
			return null;

		$isFunc = false;
		$indexes = array();

		$result = $this->query("SELECT * FROM USER_IND_COLUMNS WHERE TABLE_NAME = upper('".$this->getSqlHelper()->forSql($tableName)."')");
		while ($ar = $result->fetch())
		{
			$indexes[$ar["INDEX_NAME"]][$ar["COLUMN_POSITION"] - 1] = $ar["COLUMN_NAME"];
			if (strncmp($ar["COLUMN_NAME"], "SYS_NC", 6) === 0)
			{
				$isFunc = true;
			}
		}

		if ($isFunc)
		{
			$result = $this->query("SELECT * FROM USER_IND_EXPRESSIONS WHERE TABLE_NAME = upper('".$this->getSqlHelper()->forSql($tableName)."')");
			while ($ar = $result->fetch())
			{
				$indexes[$ar["INDEX_NAME"]][$ar["COLUMN_POSITION"] - 1] = $ar["COLUMN_EXPRESSION"];
			}
		}

		$columnsList = implode(",", $columns);
		foreach ($indexes as $indexName => $indexColumns)
		{
			ksort($indexColumns);
			$indexColumnList = implode(",", $indexColumns);
			if ($strict)
			{
				if ($indexColumnList === $columnsList)
					return $indexName;
			}
			else
			{
				if (mb_substr($indexColumnList, 0, mb_strlen($columnsList)) === $columnsList)
					return $indexName;
			}
		}

		return null;
	}

	/**
	 * Returns fields objects according to the columns of a table.
	 * Table must exists.
	 *
	 * @param string $tableName The table name.
	 *
	 * @return ScalarField[] An array of objects with columns information.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function getTableFields($tableName)
	{
		if (!isset($this->tableColumnsCache[$tableName]))
		{
			$this->connectInternal();

			$query = $this->queryInternal("SELECT * FROM ".$this->getSqlHelper()->quote($tableName)." WHERE ROWNUM = 0");

			$result = $this->createResult($query);

			$this->tableColumnsCache[$tableName] = $result->getFields();
		}
		return $this->tableColumnsCache[$tableName];
	}

	/**
	 * @param string        $tableName     Name of the new table.
	 * @param ScalarField[] $fields        Array with columns descriptions.
	 * @param string[]      $primary       Array with primary key column names.
	 * @param string[]      $autoincrement Which columns will be auto incremented ones.
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function createTable($tableName, $fields, $primary = array(), $autoincrement = array())
	{
		$sql = 'CREATE TABLE '.$this->getSqlHelper()->quote($tableName).' (';
		$sqlFields = array();

		foreach ($fields as $columnName => $field)
		{
			if (!($field instanceof ScalarField))
			{
				throw new ArgumentException(sprintf(
					'Field `%s` should be an Entity\ScalarField instance', $columnName
				));
			}

			$realColumnName = $field->getColumnName();

			$sqlFields[] = $this->getSqlHelper()->quote($realColumnName)
				. ' ' . $this->getSqlHelper()->getColumnTypeByField($field)
				. ' ' . (in_array($columnName, $primary, true) ? 'NOT NULL' : 'NULL')
			;
		}

		$sql .= join(', ', $sqlFields);

		if (!empty($primary))
		{
			foreach ($primary as &$primaryColumn)
			{
				$realColumnName = $fields[$primaryColumn]->getColumnName();
				$primaryColumn = $this->getSqlHelper()->quote($realColumnName);
			}

			$sql .= ', PRIMARY KEY('.join(', ', $primary).')';
		}

		$sql .= ')';

		$this->query($sql);

		// autoincrement field
		if (!empty($autoincrement))
		{
			foreach ($autoincrement as $autoincrementColumn)
			{
				$autoincrementColumn = $fields[$autoincrementColumn]->getColumnName();

				if ($autoincrementColumn == 'ID')
				{
					// old-school hack
					$aiName = $tableName;
				}
				else
				{
					$aiName = $tableName.'_'.$autoincrementColumn;
				}

				$this->query('CREATE SEQUENCE '.$this->getSqlHelper()->quote('sq_'.$aiName));

				$this->query('CREATE OR REPLACE TRIGGER '.$this->getSqlHelper()->quote($aiName.'_insert').'
						BEFORE INSERT
						ON '.$this->getSqlHelper()->quote($tableName).'
						FOR EACH ROW
							BEGIN
							IF :NEW.'.$this->getSqlHelper()->quote($autoincrementColumn).' IS NULL THEN
								SELECT '.$this->getSqlHelper()->quote('sq_'.$aiName).'.NEXTVAL
									INTO :NEW.'.$this->getSqlHelper()->quote($autoincrementColumn).' FROM dual;
							END IF;
						END;'
				);
			}
		}
	}

	/**
	 * Renames the table. Renamed table must exists and new name must not be occupied by any database object.
	 *
	 * @param string $currentName Old name of the table.
	 * @param string $newName New name of the table.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function renameTable($currentName, $newName)
	{
		$this->query('RENAME '.$this->getSqlHelper()->quote($currentName).' TO '.$this->getSqlHelper()->quote($newName));

		// handle auto increment: rename primary sequence for ID
		// properly we should check PRIMARY fields instead of ID: $aiName = $currentName.'_'.$fieldName, see createTable
		$aiName = $currentName;

		if ($this->queryScalar("SELECT 1 FROM user_sequences WHERE sequence_name=upper('".$this->getSqlHelper()->forSql('sq_'.$aiName)."')"))
		{
			// for fields excpet for ID here should be $newName.'_'.$fieldName, see createTable
			$newAiName = $newName;

			// rename sequence
			$this->query('RENAME '.$this->getSqlHelper()->quote('sq_'.$aiName).' TO '.$this->getSqlHelper()->quote('sq_'.$newAiName));

			// recreate trigger
			$this->query('DROP TRIGGER '.$this->getSqlHelper()->quote($aiName.'_insert'));

			$this->query('CREATE OR REPLACE TRIGGER '.$this->getSqlHelper()->quote($newAiName.'_insert').'
						BEFORE INSERT
						ON '.$this->getSqlHelper()->quote($newName).'
						FOR EACH ROW
							BEGIN
							IF :NEW.'.$this->getSqlHelper()->quote('ID').' IS NULL THEN
								SELECT '.$this->getSqlHelper()->quote('sq_'.$newAiName).'.NEXTVAL
									INTO :NEW.'.$this->getSqlHelper()->quote('ID').' FROM dual;
							END IF;
						END;'
			);
		}
	}

	/**
	 * Drops the table.
	 *
	 * @param string $tableName Name of the table to be dropped.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function dropTable($tableName)
	{
		$this->query('DROP TABLE '.$this->getSqlHelper()->quote($tableName).' CASCADE CONSTRAINTS');

		// handle auto increment: delete primary sequence for ID
		// properly we should check PRIMARY fields instead of ID: $aiName = $currentName.'_'.$fieldName, see createTable
		$aiName = $tableName;

		if ($this->queryScalar("SELECT 1 FROM user_sequences WHERE sequence_name=upper('".$this->getSqlHelper()->forSql('sq_'.$aiName)."')"))
		{
			$this->query('DROP SEQUENCE '.$this->getSqlHelper()->quote('sq_'.$aiName));
		}
	}

	/*********************************************************
	 * Transaction
	 *********************************************************/

	/**
	 * Starts new database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function startTransaction()
	{
		$this->transaction = OCI_DEFAULT;
	}

	/**
	 * Commits started database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function commitTransaction()
	{
		$this->connectInternal();
		OCICommit($this->resource);
		$this->transaction = OCI_COMMIT_ON_SUCCESS;
	}

	/**
	 * Rollbacks started database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function rollbackTransaction()
	{
		$this->connectInternal();
		OCIRollback($this->resource);
		$this->transaction = OCI_COMMIT_ON_SUCCESS;
	}

	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	/**
	 * Returns database type.
	 * <ul>
	 * <li> oracle
	 * </ul>
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\Connection::getType
	 */
	public function getType()
	{
		return "oracle";
	}

	/**
	 * Returns connected database version.
	 * Version presented in array of two elements.
	 * - First (with index 0) is database version.
	 * - Second (with index 1) is true when light/express version of database is used.
	 *
	 * @return array
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function getVersion()
	{
		if ($this->version == null)
		{
			$version = $this->queryScalar('SELECT BANNER FROM v$version');
			if ($version != null)
			{
				$version = trim($version);
				$this->versionExpress = (mb_strpos($version, "Express Edition") > 0);
				preg_match("#[0-9]+\\.[0-9]+\\.[0-9]+#", $version, $arr);
				$this->version = $arr[0];
			}
		}

		return array($this->version, $this->versionExpress);
	}

	/**
	 * Returns error message of last failed database operation.
	 *
	 * @param resource $resource Connection or query result resource.
	 * @return string
	 */
	protected function getErrorMessage($resource = null)
	{
		if ($resource)
			$error = oci_error($resource);
		else
			$error = oci_error();

		if (!$error)
			return "";

		$result = sprintf("[%s] %s", $error["code"], $error["message"]);
		if (!empty($error["sqltext"]))
			$result .= sprintf(" (%s)", $error["sqltext"]);

		return $result;
	}
}
