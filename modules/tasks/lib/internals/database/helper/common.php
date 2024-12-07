<?php
/**
 * Bitrix Framework
 * @package tasks
 * @copyright 2001-2015 Bitrix
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Internals\DataBase\Helper;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\DB;

abstract class Common
{
	public static function parseFilter(array $filter)
	{
		$parsed = array();
		$parser = new \CAllSQLWhere();
		foreach($filter as $k => $v)
		{
			$info = $parser->makeOperation($k);
			$info['VALUE'] = $v;
			$info['ORIG_KEY'] = $k;

			$op = $info['OPERATION'];
			$info['NOT'] = ($op == 'NB' || $op == 'NM' || $op == 'SN' || $op == 'NI' || $op == 'NS' || $op == 'NIN' || $op == 'N');

			$parsed[] = $info;
		}

		return $parsed;
	}

	/**
	 * todo: this function was tested only on trivial filters
	 * todo: this function does not support nested filters
	 *
	 * @param array $conditions
	 * @return array
	 */
	public static function makeFilter(array $conditions)
	{
		$result = array();

		foreach($conditions as $condition)
		{
			if(array_key_exists('ORIG_KEY', $condition))
			{
				$result[$condition['ORIG_KEY']] = $condition['VALUE'];
			}
			else
			{
				$op = \CAllSQLWhere::getOperationByCode($condition['OPERATION']);
				$result[$op.$condition['FIELD']] = $condition['VALUE'];
			}
		}

		return $result;
	}

	public static function getTruncateTextFunction($columnName)
	{
		return $columnName;
	}

	public static function checkColumnExists($tableName, $columnName)
	{
		if((string) $tableName == '' || (string) $columnName == '')
		{
			return false;
		}

		try
		{
			@\Bitrix\Main\HttpApplication::getConnection()->query("select ".$columnName." from ".$tableName." WHERE 1=0");
			return true;
		}
		catch(\Bitrix\Main\DB\SqlQueryException $e)
		{
			return false;
		}
	}

	public static function checkIsType($type)
	{
		if((string) $type === '')
		{
			return false;
		}

		return mb_strtoupper(Main\HttpApplication::getConnection()->getType()) == mb_strtoupper((string) $type);
	}

	public static function getDataTypeSql($type, $len = 0)
	{
		if($type == 'int')
		{
			return $len == 3 ? 'tinyint' : 'int';
		}

		if($type == 'varchar')
		{
			return 'varchar('.(intval($len) ? intval($len) : '1').')';
		}

		if($type == 'char')
		{
			return 'char('.(intval($len) ? intval($len) : '1').')';
		}

		if($type == 'text')
		{
			return 'text';
		}

		if($type == 'datetime')
		{
			return 'datetime';
		}

		return '';
	}

	public static function wrapColumnWithFunction($columnName, $functions = array())
	{
		return $columnName; // do nothing for abstract db
	}

	public static function getMaxTransferUnit()
	{
		return PHP_INT_MAX;
	}

	/*
	public static function getBatchInsertHeadSql($tableName, $fields = array())
	{
		$map = array();

		$dbHelper = Main\HttpApplication::getConnection()->getSqlHelper();

		if(is_array($fields))
		{
			foreach($fields as $fld)
				$map[] = $dbHelper->forSql($fld);
		}

		return 'insert into '.$dbHelper->forSql($tableName).' ('.implode(',', $map).') values ';
	}

	public static function getBatchInsertTailSql()
	{
		return '';
	}

	public static function getBatchInsertSeparatorSql()
	{
		return ', ';
	}

	public static function getBatchInsertValues($row, $tableName, $fields, $map)
	{
		return static::prepareSql($row, $fields, $map);
	}

	// makes sense only for mssql
	public static function dropAutoIncrementRestrictions($tableName)
	{
		return false;
	}

	// same
	public static function restoreAutoIncrementRestrictions($tableName)
	{
		return false;
	}

	// makes sense only for oracle
	public static function incrementSequenceForTable($tableName)
	{
		return false;
	}

	public static function addPrimaryKey($tableName, $columns = array())
	{
		if(!strlen($tableName) || !is_array($columns) || empty($columns))
			return false;

		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$tableName = $dbHelper->forSql($tableName);
		$columns = static::escapeArray($columns);

		$dbConnection->query("ALTER TABLE ".$tableName." ADD CONSTRAINT PK_".ToUpper($tableName)." PRIMARY KEY (".implode(', ', $columns).")");

		return true;
	}
	*/

	// do nothing but for oracle
	public static function addAutoIncrement()
	{
		return false;
	}

	public static function createIndex($tableName, $ixNamePostfix, $columns = array(), $unique = false)
	{
		if(!mb_strlen($tableName) || !mb_strlen($ixNamePostfix) || !is_array($columns) || empty($columns))
			return false;

		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$tableName = 		$dbHelper->forSql($tableName);
		$ixNamePostfix = 	$dbHelper->forSql($ixNamePostfix);
		$columns = 			static::escapeArray($columns);

		$ixName = static::getIndexName($tableName, $ixNamePostfix, $columns);

		if(mb_strlen($ixName) > 30)
			return false;

		if(!static::checkIndexNameExists($ixName, $tableName))
		{
			$dbConnection->query("CREATE ".($unique ? "UNIQUE" : "")." INDEX ".$ixName." ON ".$tableName." (".implode(', ', $columns).")");
			return true;
		}

		return false;
	}

	public static function dropTable($tableName)
	{
		$dbConnection = Main\HttpApplication::getConnection();

		$tableName = $dbConnection->getSqlHelper()->forSql($tableName);

		if($dbConnection->isTableExists($tableName))
			Main\HttpApplication::getConnection()->query('drop table '.$tableName);
	}

	public static function checkTableExists($tableName)
	{
		return Main\HttpApplication::getConnection()->isTableExists($tableName);
	}

	public static function truncateTable($tableName)
	{
		$dbConnection = Main\HttpApplication::getConnection();

		$tableName = $dbConnection->getSqlHelper()->forSql($tableName);

		if($dbConnection->isTableExists($tableName))
			Main\HttpApplication::getConnection()->query('truncate table '.$tableName);
	}

	public static function getTemporaryTableSubQuerySql($selectSql, $columnName)
	{
		return "select ".$columnName." from (".$selectSql.") as ".static::getTemporaryTableNameSql();
	}

	public static function getTemporaryTableNameSql()
	{
		return "tmp_table_".rand(99, 9999);
	}

	public static function checkIndexNameExists($indexName, $tableName)
	{
		return false;
	}

	/**
	 * Inserts rows in batch mode.
	 *
	 * @param $tableName
	 * @param array $items
	 *
	 *
	 * todo: refactor this, get rid of 'if condition'
	 */
	public static function insertBatch($tableName, array $items, bool $ignore = false)
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = $prefix = '';

		foreach ($items as $item)
		{
			list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);

			$query .= ($query? ', ' : ' ') . '(' . $values . ')';
			if(mb_strlen($query) > 2048)
			{
				$sqlQuery = "INSERT INTO {$tableName} ({$prefix}) VALUES {$query}";
				if ($ignore)
				{
					$sqlQuery = $sqlHelper->getInsertIgnore(
						$tableName,
						" ({$prefix})",
						" VALUES {$query}"
					);
				}
				$connection->queryExecute($sqlQuery);
				$query = '';
			}
		}
		unset($item);

		$sqlQuery = "INSERT INTO {$tableName} ({$prefix}) VALUES {$query}";
		if ($ignore)
		{
			$sqlQuery = $sqlHelper->getInsertIgnore(
				$tableName,
				" ({$prefix})",
				" VALUES {$query}"
			);
		}

		if ($query && $prefix)
		{
			$connection->queryExecute($sqlQuery);
		}
	}

	//////////////

	protected static function prepareSql($row, $fields, $map)
	{
		if(!is_array($row) || empty($row) || !is_array($fields) || empty($fields) || !is_array($map) || empty($map))
			return '';

		$sql = array();
		foreach($fields as $fld => $none)
		{
			$val = $row[$fld];

			// only numeric and literal fields supported at the moment
			if($map[$fld]['data_type'] == 'integer')
				$sql[] = intval($val);
			else
				$sql[] = "'".Main\HttpApplication::getConnection()->getSqlHelper()->forSql($val)."'";
		}

		return '('.implode(',', $sql).')';
	}

	// makes sense only for oracle
	protected static function checkSequenceExistsForTable($tableName)
	{
		return false;
	}

	protected static function getIndexName($tableName, $ixNamePostfix, $columns = array())
	{
		return 'IX_'.preg_replace('#^B_#', '', mb_strtoupper($tableName))."_".mb_strtoupper($ixNamePostfix);
	}

	protected static function escapeArray($columns)
	{
		foreach($columns as &$col)
		{
			$col = Main\HttpApplication::getConnection()->getSqlHelper()->forSql($col);
		}
		unset($col);

		return $columns;
	}
}