<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


class CTaskTags
{
	function CheckFields(&$arFields, /** @noinspection PhpUnusedParameterInspection */ $ID = false, $effectiveUserId = null)
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
			$arParams = array();
			if ($effectiveUserId !== null)
				$arParams['USER_ID'] = $effectiveUserId;

			$r = CTasks::GetByID($arFields["TASK_ID"], true, $arParams);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID_EX"), "id" => "ERROR_TASKS_BAD_TASK_ID_EX");
			}
		}

		if ($effectiveUserId !== null && !isset($arFields['USER_ID']))
			$arFields['USER_ID'] = $effectiveUserId;

		if (!is_set($arFields, "USER_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_USER_ID"), "id" => "ERROR_TASKS_BAD_USER_ID");
		}
		else
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$r = CUser::GetByID($arFields["USER_ID"]);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_USER_ID_EX"), "id" => "ERROR_TASKS_BAD_USER_ID_EX");
			}
		}

		if (!is_set($arFields, "NAME") || strlen(trim($arFields["NAME"])) <= 0)
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_NAME"), "id" => "ERROR_BAD_TASKS_NAME");
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}

	function Add($arFields, $effectiveUserId = null)
	{
		if ($this->CheckFields($arFields, false, $effectiveUserId))
		{
			$result = \Bitrix\Tasks\TagTable::add(array(
				"TASK_ID" => $arFields["TASK_ID"],
				"USER_ID" => $arFields["USER_ID"],
				"NAME" => $arFields["NAME"],
			));
			if ($result->isSuccess())
			{
				return $result->getId();
			}
		}
		return false;
	}

	function GetFilter($arFilter)
	{
		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val)
		{
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);

			switch ($key)
			{
				case "TASK_ID":
				case "USER_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "NAME":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}

	public static function getTagsNamesByUserId($userId, $limit = 100)
	{
		global $DB;

		$userId = (int) $userId;

		return ($DB->query(
			"SELECT DISTINCT TT.NAME 
			FROM b_tasks_tag TT 
			WHERE TT.USER_ID = $userId
			ORDER BY TT.NAME ASC
			LIMIT ".(int)$limit
		));
	}

	public static function GetList($arOrder, $arFilter)
	{
		global $DB;

		$arSqlSearch = array_filter(CTaskTags::GetFilter($arFilter));

		$strSql = "
			SELECT
				TT.*
			FROM
				b_tasks_tag TT
			".(sizeof($arSqlSearch) ? "WHERE ".implode(" AND ", $arSqlSearch) : "")."
		";

		if (!is_array($arOrder))
			$arOrder = Array();

		$arSqlOrder = [];
		foreach ($arOrder as $by => $order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order != "asc")
				$order = "desc";

			if ($by == "task")
				$arSqlOrder[] = " TT.TASK_ID ".$order." ";
			elseif ($by == "user")
				$arSqlOrder[] = " TT.USER_ID ".$order." ";
			elseif ($by == "name")
				$arSqlOrder[] = " TT.NAME ".$order." ";
			elseif ($by == "rand")
				$arSqlOrder[] = CTasksTools::getRandFunction();
			else
				$arSqlOrder[] = " TT.TASK_ID ".$order." ";
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

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	function DeleteByName($NAME)
	{
		return self::Delete(array("=NAME" => $NAME));
	}

	public static function DeleteByTaskID($TASK_ID)
	{
		return self::Delete(array("=TASK_ID" => (int) $TASK_ID));
	}

	function DeleteByUserID($USER_ID)
	{
		return self::Delete(array("=USER_ID" => (int) $USER_ID));
	}

	function Rename($OLD_NAME, $NEW_NAME, $USER_ID)
	{
		$tasks = array();
		$list = \Bitrix\Tasks\TagTable::getList(array(
			"select" => array("TASK_ID", "USER_ID", "NAME"),
			"filter" => array(
				"=USER_ID" => intval($USER_ID),
				"=NAME" => $OLD_NAME,
			),
		));
		while ($item = $list->fetch())
		{
			$tasks[] = $item;
		}

		foreach ($tasks as $primary)
		{
			\Bitrix\Tasks\TagTable::delete($primary);
			\Bitrix\Tasks\TagTable::add(array_merge($primary, array("NAME" => $NEW_NAME)));
		}

		return true;
	}

	function Delete($arFilter)
	{
		$result = false;
		if ($arFilter)
		{
			$list = \Bitrix\Tasks\TagTable::getList(array(
				"filter" => $arFilter,
			));
			while ($item = $list->fetch())
			{

				$result = \Bitrix\Tasks\TagTable::delete($item);
			}
			return $result;
		}
	}

}