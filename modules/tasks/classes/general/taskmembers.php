<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @deprecated
 * @see \Bitrix\Tasks\Internals\Task\MemberTable
 *
 * This class handles only A- and U-type items. The rest of the table content lays outside the class scope.
 */

use Bitrix\Tasks\Internals\Task\MemberTable;

class CTaskMembers
{
	function CheckFields(&$arFields, /** @noinspection PhpUnusedParameterInspection */ $ID = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arMsg = array();

		if (!is_set($arFields, "TASK_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID"), "id" => "ERROR_TASKS_BAD_TASK_ID");
		}
		else
		{
			/** @noinspection PhpDeprecationInspection */
			$r = CTasks::GetByID($arFields["TASK_ID"], false);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID_EX"), "id" => "ERROR_TASKS_BAD_TASK_ID_EX");
			}
		}

		if (!is_set($arFields, "USER_ID") || !intval($arFields["USER_ID"]))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_USER_ID"), "id" => "ERROR_TASKS_BAD_USER_ID");
		}
		else
		{

			$r = CUser::GetByID($arFields["USER_ID"]);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_USER_ID_EX"), "id" => "ERROR_TASKS_BAD_USER_ID_EX");
			}
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		//Defaults
		if (!is_set($arFields, "TYPE") || !in_array($arFields["TYPE"], Array("R", "O", "A", "U")))
			$arFields["TYPE"] = "A";

		return true;
	}


	function Add($arFields)
	{
		if ($this->CheckFields($arFields))
		{
			$arMember = array(
				"TASK_ID" => $arFields["TASK_ID"],
				"USER_ID" => $arFields["USER_ID"],
				"TYPE" => $arFields["TYPE"],
			);
			// we cannot use prepareMerge() here, we want orm events
			$check = MemberTable::getByPrimary($arMember)->fetch();
			if(!$check) // still dont have
			{
				$result = MemberTable::add($arMember);
				if ($result->isSuccess())
				{
					return $result->getId();
				}
			}
		}
		
		return false;
	}


	private static function GetFilter($arFilter)
	{
		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = [];

		if(!array_key_exists('TYPE', $arFilter))
		{
			$arSqlSearch = [
				"TM.TYPE in ('A', 'U')" // restrict only this types
			];
		}
		foreach ($arFilter as $key => $val)
		{
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = mb_strtoupper($key);

			switch ($key)
			{
				case "TASK_ID":
				case "USER_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TM.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "TYPE":
					$arSqlSearch[] = CTasks::FilterCreate("TM.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}


	public static function GetList($arOrder, $arFilter)
	{
		global $DB;

		$arSqlSearch = array_filter(static::GetFilter($arFilter));

		$strSqlSearch = "";
		$arSqlSearchCnt = count($arSqlSearch);
		for ($i = 0; $i < $arSqlSearchCnt; $i++)
			if ($arSqlSearch[$i] <> '')
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$strSql = "
			SELECT
				TM.*
			FROM
				b_tasks_member TM
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

			if (($by === 'task') || ($by === 'task_id'))
				$arSqlOrder[] = " TM.TASK_ID ".$order." ";
			elseif (($by === 'user') || ($by === 'user_id'))
				$arSqlOrder[] = " TM.USER_ID ".$order." ";
			elseif ($by === 'type')
				$arSqlOrder[] = " TM.TYPE ".$order." ";
			elseif ($by == "rand")
				$arSqlOrder[] = CTasksTools::getRandFunction();
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

		return $DB->Query($strSql);
	}


	function DeleteByUserID($USER_ID)
	{
		$result = false;
		$list = MemberTable::getList(array(
			"filter" => array(
				"=USER_ID" => $USER_ID,
				"=TYPE" => array("A", "U"), // remove only of that type
			),
		));
		while ($item = $list->fetch())
		{
			$result = MemberTable::delete($item);
		}
		return $result;
	}


	function DeleteByTaskID($TASK_ID, $TYPE = null)
	{
		$result = false;
		$filter = array(
			"=TASK_ID" => $TASK_ID,
		);
		if ($TYPE != null && in_array($TYPE, array("A", "U")))
		{
			$filter["=TYPE"] = $TYPE;
		}
        else
        {
            $filter['=TYPE'] = array("A", "U"); // remove only of that type
        }

		$list = MemberTable::getList(array(
			"filter" => $filter,
		));
		while ($item = $list->fetch())
		{
			$result = MemberTable::delete($item);
		}

		return $result;
	}

	/**
	 * @param $taskId
	 * @param array $userIds
	 * @param $type
	 * @throws Exception
	 * @throws \Bitrix\Main\ArgumentException
	 *
	 * @internal
	 *
	 * This function is temporal
	 */
	public static function updateForTask($taskId, array $userIds, $type)
	{
		// drop previous
		$list = MemberTable::getList(array(
			"filter" => array(
				'=TYPE' => $type,
				'=TASK_ID' => $taskId,
			),
		));
		while ($item = $list->fetch())
		{
			MemberTable::delete($item);
		}

		// add new
		foreach($userIds as $userId)
		{
			MemberTable::add(array(
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
				'TYPE' => $type,
			));
		}
	}

	public static function DeleteAllByTaskID($TASK_ID)
	{
		$result = false;
		$filter = array(
			"=TASK_ID" => $TASK_ID,
		);
		$list = MemberTable::getList(array(
			"filter" => $filter,
		));
		while ($item = $list->fetch())
		{
			$result = MemberTable::delete($item);
		}

		return $result;
	}

}