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

namespace Bitrix\Tasks\Internals\DataBase;

use Bitrix\Main;

final class Helper extends \Bitrix\Tasks\Internals\DataBase\Helper\Common
{
	public static function getTruncateTextFunction($columnName)
	{
		return "TO_CHAR(SUBSTR(".$columnName.", 1, 1024))";
	}

	public static function getDataTypeSql($type, $len = 0)
	{
		if($type == 'int')
		{
			return 'NUMBER('.($len > 0 ? $len : 18).')';
		}

		if($type == 'varchar')
		{
			return 'VARCHAR('.(intval($len) ? intval($len) : '1').' CHAR)';
		}

		if($type == 'char')
		{
			return 'CHAR('.(intval($len) ? intval($len) : '1').' CHAR)';
		}

		if($type == 'text')
		{
			return 'CLOB';
		}

		if($type == 'datetime')
		{
			return 'DATE';
		}

		return '';
	}

	public static function wrapColumnWithFunction($columnName, $functions = array())
	{
		if(!is_array($functions))
			$functions = array();

		$allowed = array('to_char' => true);

		foreach($functions as $func)
		{
			if(isset($allowed[$func]))
				$columnName = $func.'('.$columnName.')';
		}

		return $columnName; // do nothing for abstract db
	}

	public static function getAutoIncrementSql()
	{
		return '';
	}

	public static function mergeTables($toTable, $fromTable, $fldMap, $fldCondition)
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$toTable = $dbHelper->forSql(trim($toTable));
		$fromTable = $dbHelper->forSql(trim($fromTable));

		if(!strlen($toTable) || !strlen($toTable) || !is_array($fldMap) || empty($fldMap) || empty($fldCondition))
			return false;

		// update tab1 set (aa,bb) = (select aa,bb from tab2 where tab2.cc = tab1.dd) where exists (select 1 from tab2 where tab2.cc = tab1.dd)

		$toFlds = array();
		$fromFlds = array();
		foreach($fldMap as $toFld => $fromFld)
		{
			$toFlds[] = $dbHelper->forSql(trim($toFld));
			$fromFlds[] = $dbHelper->forSql(trim($fromFld));
		}

		$where = array();
		foreach($fldCondition as $left => $right)
			$where[] = $toTable.'.'.$dbHelper->forSql(trim($left)).' = '.$fromTable.'.'.$dbHelper->forSql(trim($right));

		$sql = 'update '.$toTable.' set ('.
			implode(', ', $toFlds).
		') = (select '.
			implode(', ', $fromFlds).
		' from '.$fromTable.' where '.implode(' and ', $where).') where exists (select 1 from '.$fromTable.' where '.implode(' and ', $where).')';

		$dbConnection->query($sql);

		return true;
	}

	public static function checkIndexNameExists($indexName, $tableName)
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$indexName = trim($indexName);
		$tableName = $dbHelper->forSql(trim($tableName));

		if(!strlen($indexName) || !strlen($tableName))
			return false;

		$res = $dbConnection->query("SELECT INDEX_NAME as Key_name FROM USER_IND_COLUMNS WHERE TABLE_NAME = '".ToUpper($tableName)."'");

		while($item = $res->fetch())
		{
			if($item['Key_name'] == $indexName || $item['KEY_NAME'] == $indexName)
				return true;
		}

		return false;
	}

	public static function dropIndexByName($indexName, $tableName)
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$indexName = $dbHelper->forSql(trim($indexName));
		$tableName = $dbHelper->forSql(trim($tableName));

		if(!strlen($indexName) || !strlen($tableName))
			return false;

		if(!static::checkIndexNameExists($indexName, $tableName))
			return false;

		$dbConnection->query("drop index {$indexName}");

		return true;
	}

	/*
	public static function getBatchInsertHeadSql($fields)
	{
		return 'insert all ';
	}

	public static function getBatchInsertTailSql()
	{
		return ' select * from dual';
	}

	public static function getBatchInsertSeparatorSql()
	{
		return ' ';
	}

	public static function getBatchInsertValues($row, $tableName, $fields, $map)
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$eMap = $map;

		if(!is_array($eMap))
			$eMap = array();

		foreach($eMap as &$fld)
		{
			$fld = $dbHelper->forSql($fld);
		}
		unset($fld);

		return 'into '.$dbHelper->forSql($tableName).' ('.implode(',', $eMap).') values '.static::prepareSql($row, $fields, $map);
	}

	public static function incrementSequenceForTable($tableName)
	{
		$dbConnection = Main\HttpApplication::getConnection();

		if($sqName = Helper::checkSequenceExistsForTable($tableName))
			$dbConnection->query('select '.$sqName.'.NEXTVAL from dual');
	}
	*/

	protected static function checkSequenceExistsForTable($tableName)
	{
		if(!strlen($tableName))
			return false;

		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$sequenceName = 'SQ_'.ToUpper($dbHelper->forSql($tableName));
		if(!($dbConnection->query("select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = '".$sequenceName."'", true)->fetch()))
			return false;

		return $sequenceName;
	}

	// this function is used to adjust auto_increment value of a table to a certain position
	public static function resetAutoIncrement($tableName, $startIndex = 1)
	{
		$startIndex = intval($startIndex);
		if($startIndex <= 0 || !strlen($tableName))
			return false;

		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		$tableName = $dbHelper->forSql(trim($tableName));

		if(strlen($tableName) > 27) // too long
			return false;

		$sqName = 'SQ_'.ToUpper($dbHelper->forSql($tableName));
		$checkSql = "select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = '".$sqName."'";
		if($dbConnection->query($checkSql)->fetch())
			$dbConnection->query('drop sequence '.$sqName);

		$dbConnection->query('create sequence '.$sqName.' start with '.$startIndex.' increment by 1 NOMAXVALUE NOCYCLE NOCACHE NOORDER');
		$dbConnection->query("
			CREATE OR REPLACE TRIGGER ".$tableName."_I
			BEFORE INSERT
			ON ".$tableName."
			FOR EACH ROW
			BEGIN
				IF :NEW.ID IS NULL THEN
					SELECT ".$sqName.".NEXTVAL INTO :NEW.ID FROM dual;
				END IF;
			END;
		");

		return true;
	}

	public static function addAutoIncrement($tableName)
	{
		if(static::checkSequenceExistsForTable($tableName))
			return;

		static::resetAutoIncrement($tableName);
	}

	public static function getTemporaryTableSubQuerySql($selectSql, $columnName)
	{
		return $selectSql;
	}
}