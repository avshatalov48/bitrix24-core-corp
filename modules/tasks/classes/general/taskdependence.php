<?php

use Bitrix\Main\Application;

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

class CTaskDependence
{

	function CheckFields(&$arFields)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arMsg = Array();

		if (!is_set($arFields, "TASK_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID"), "id" => "ERROR_TASKS_BAD_TASK_ID");
		}
		else
		{
			$r = CTasks::GetByID($arFields["TASK_ID"]);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID_EX"), "id" => "ERROR_TASKS_BAD_TASK_ID_EX");
			}
		}

		if (!is_set($arFields, "DEPENDS_ON_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_DEPENDS_ON_ID"), "id" => "ERROR_TASKS_BAD_DEPENDS_ON_ID");
		}
		else
		{
			$r = CTasks::GetByID($arFields["DEPENDS_ON_ID"]);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_DEPENDS_ON_ID_EX"), "id" => "ERROR_TASKS_BAD_DEPENDS_ON_ID_EX");
			}
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}

	function Add($arFields)
	{
		if ($this->CheckFields($arFields))
		{
			$connection = Application::getConnection();
			$helper = $connection->getSqlHelper();
			$insert = $helper->prepareInsert('b_tasks_dependence', $arFields);

			$query = $helper->getInsertIgnore(
				'b_tasks_dependence',
				" ({$insert[0]})",
				" VALUES ({$insert[1]})"
			);

			$connection->query($query);

			return 0; // always 0, no ID in the table
		}

		return false;
	}

	function Delete($TASK_ID, $DEPENDS_ON_ID)
	{
		global $DB;

		$TASK_ID = intval($TASK_ID);
		$DEPENDS_ON_ID = intval($DEPENDS_ON_ID);
		$strSql = "DELETE FROM b_tasks_dependence WHERE TASK_ID = ".$TASK_ID." AND DEPENDS_ON_ID = ".$DEPENDS_ON_ID;
		return $DB->Query($strSql);
	}

	public static function GetFilter($arFilter)
	{
		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val)
		{
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = mb_strtoupper($key);

			switch ($key)
			{
				case "TASK_ID":
				case "DEPENDS_ON_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TD.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}

	public static function GetList($arOrder, $arFilter)
	{
		global $DB;

		$arSqlSearch = CTaskDependence::GetFilter($arFilter);

		$strSql = "
			SELECT
				TD.*
			FROM
				b_tasks_dependence TD
			".(sizeof($arSqlSearch) ? "WHERE ".implode(" AND ", $arSqlSearch) : "")."
		";

		if (!is_array($arOrder))
			$arOrder = Array();

		$arSqlOrder = [];
		foreach ($arOrder as $by => $order)
		{
			$by = mb_strtolower($by);
			$order = mb_strtolower($order);
			if ($order != "asc")
				$order = "desc";

			if ($by == "task")
				$arSqlOrder[] = " TD ".$order." ";	// WTF?!
			elseif ($by == "depends_on")
				$arSqlOrder[] = " TD.DEPENDS_ON ".$order." ";	// is it for back compatibility?!
			elseif ($by == "depends_on_id")
				$arSqlOrder[] = " TD.DEPENDS_ON_ID ".$order." ";
			elseif ($by == "task_id")
				$arSqlOrder[] = " TD.TASK_ID ".$order." ";
			elseif ($by == "rand")
				$arSqlOrder[] = CTasksTools::getRandFunction();
			else
				$arSqlOrder[] = " TD.ID ".$order." ";	// is it for back compatibility?!
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		$arSqlOrderCnt = count($arSqlOrder);
		for ($i = 0; $i < $arSqlOrderCnt; $i++)
		{
			if ($i == 0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		//echo $strSql;

		return $DB->Query($strSql);
	}

	public static function DeleteByDependsOnID($DEPENDS_ON)
	{
		global $DB;

		$DEPENDS_ON = intval($DEPENDS_ON);
		$strSql = "DELETE FROM b_tasks_dependence WHERE DEPENDS_ON_ID = ".$DEPENDS_ON;
		return $DB->Query($strSql);
	}

	public static function DeleteByTaskID($TASK_ID)
	{
		global $DB;

		$TASK_ID = intval($TASK_ID);
		$strSql = "DELETE FROM b_tasks_dependence WHERE TASK_ID = ".$TASK_ID;
		return $DB->Query($strSql);
	}
}