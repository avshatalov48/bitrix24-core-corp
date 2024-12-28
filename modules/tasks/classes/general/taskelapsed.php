<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

/**
 * This class is deprecated. Use CTaskElapsedItem instead.
 * Behaviour of this class not corresponds to new rights model.
 *
 * @deprecated
 */
class CTaskElapsedTime
{
	function CheckFields(/** @noinspection PhpUnusedParameterInspection */ &$arFields, /** @noinspection PhpUnusedParameterInspection */ $ID = false)
	{
		return true;
	}


	function Add($arFields, $arParams = array())
	{
		global $DB;

		$executiveUserId = null;
		if (isset($arParams['USER_ID']))
			$executiveUserId = (int) $arParams['USER_ID'];
		elseif ($userId = \Bitrix\Tasks\Util\User::getId())
			$executiveUserId = $userId;

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if ( ! $occurAsUserId )
			$occurAsUserId = ($executiveUserId ? $executiveUserId : 1);

		if ($this->CheckFields($arFields))
		{
			$curDuration = 0;
			$rsTask = CTasks::getList(
				array(),
				array('ID' => $arFields['TASK_ID']),
				array('ID', 'TIME_SPENT_IN_LOGS')
			);
			if ($rsTask && ($arTask = $rsTask->fetch()))
				$curDuration = (int) $arTask['TIME_SPENT_IN_LOGS'];

			// Maintance backward compatibility
			if (isset($arFields['MINUTES'], $arFields['SECONDS']))
			{
				CTaskAssert::assert(false);
			}

			if (isset($arFields['SECONDS']))
				$arFields['MINUTES'] = (int) round($arFields['SECONDS'] / 60, 0);
			elseif (isset($arFields['MINUTES']))
				$arFields['SECONDS'] = 60 * $arFields['MINUTES'];
			else
			{
				CTaskAssert::assert(false);
			}

			foreach(GetModuleEvents('tasks', 'OnBeforeTaskElapsedTimeAdd', true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
					return false;
			}

			$arFields['SOURCE'] = CTaskElapsedItem::SOURCE_MANUAL;
			if (isset($arParams['SOURCE_SYSTEM']) && ($arParams['SOURCE_SYSTEM'] === 'Y'))
				$arFields['SOURCE'] = CTaskElapsedItem::SOURCE_SYSTEM;

			if ($arFields['CREATED_DATE'] ?? null)
				$createdDate = Bitrix\Main\Type\DateTime::createFromUserTime($arFields['CREATED_DATE']);
			else
				$createdDate = new Bitrix\Main\Type\DateTime();

			$addResult = \Bitrix\Tasks\Internals\Task\ElapsedTimeTable::add(array(
				"CREATED_DATE" => $createdDate,
				"DATE_START" => Bitrix\Main\Type\DateTime::createFromUserTime($arFields['DATE_START'] ?? null),
				"DATE_STOP" => Bitrix\Main\Type\DateTime::createFromUserTime($arFields['DATE_STOP'] ?? null),
				"USER_ID" => $arFields["USER_ID"],
				"TASK_ID" => $arFields["TASK_ID"],
				"MINUTES" => $arFields["MINUTES"],
				"SECONDS" => $arFields["SECONDS"],
				"SOURCE" => $arFields["SOURCE"],
				"COMMENT_TEXT" => $arFields["COMMENT_TEXT"],
			));

			$ID = $addResult->isSuccess()? $addResult->getId(): false;

			$oLog = new CTaskLog();
			$oLog->Add(array(
				'TASK_ID'       =>  $arFields['TASK_ID'],
				'USER_ID'       =>  $occurAsUserId,
				'~CREATED_DATE' =>  $DB->currentTimeFunction(),
				'FIELD'         => 'TIME_SPENT_IN_LOGS',
				'FROM_VALUE'    =>  $curDuration,
				'TO_VALUE'      =>  $curDuration + (int) $arFields['SECONDS']
			));

			foreach(GetModuleEvents('tasks', 'OnTaskElapsedTimeAdd', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			return $ID;
		}

		return false;
	}


	function Update($ID, $arFields, $arParams = array())
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		$executiveUserId = null;
		if (isset($arParams['USER_ID']))
			$executiveUserId = (int) $arParams['USER_ID'];
		elseif ($userId = \Bitrix\Tasks\Util\User::getId())
			$executiveUserId = $userId;

		if ($this->CheckFields($arFields, $ID))
		{
			/** @noinspection PhpDeprecationInspection */
			$rsUpdatingLogItem = self::getByID($ID);

			if ($rsUpdatingLogItem && ($arUpdatingLogItem = $rsUpdatingLogItem->fetch()))
				$taskId = $arUpdatingLogItem['TASK_ID'];
			else
				return (false);

			$curDuration = 0;
			$rsTask = CTasks::getList(
				array(),
				array('ID' => $taskId),
				array('ID', 'TIME_SPENT_IN_LOGS')
			);
			if ($rsTask && ($arTask = $rsTask->fetch()))
				$curDuration = (int) $arTask['TIME_SPENT_IN_LOGS'];

			unset($arFields["ID"]);

			$arBinds = array(
				"COMMENT_TEXT" => $arFields["COMMENT_TEXT"]
			);

			// Maintance backward compatibility
			if (isset($arFields['MINUTES'], $arFields['SECONDS']))
			{
				CTaskAssert::assert(false);
			}

			if (isset($arFields['SECONDS']))
				$arFields['MINUTES'] = (int) round((int)$arFields['SECONDS'] / 60, 0);
			elseif (isset($arFields['MINUTES']))
				$arFields['SECONDS'] = 60 * $arFields['MINUTES'];
			else
			{
				CTaskAssert::assert(false);
			}

			foreach(GetModuleEvents('tasks', 'OnBeforeTaskElapsedTimeUpdate', true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array($ID, $arUpdatingLogItem, &$arFields))===false)
					return false;
			}

			// If time changed - set CTaskElapsedItem::SOURCE_MANUAL flag
			if (
				($arUpdatingLogItem['MINUTES'] != $arFields['MINUTES'])
				|| ($arUpdatingLogItem['SECONDS'] != $arFields['SECONDS'])
			)
			{
				$arFields['SOURCE'] = CTaskElapsedItem::SOURCE_MANUAL;
			}

			$update = array();
			if (array_key_exists("CREATED_DATE", $arFields))
				$update["CREATED_DATE"] = Bitrix\Main\Type\DateTime::createFromUserTime($arFields['CREATED_DATE']);
			if (array_key_exists("DATE_START", $arFields))
				$update["DATE_START"] = Bitrix\Main\Type\DateTime::createFromUserTime($arFields['DATE_START']);
			if (array_key_exists("DATE_STOP", $arFields))
				$update["DATE_STOP"] = Bitrix\Main\Type\DateTime::createFromUserTime($arFields['DATE_STOP']);
			if (array_key_exists("USER_ID", $arFields))
				$update["USER_ID"] = $arFields['USER_ID'];
			if (array_key_exists("TASK_ID", $arFields))
				$update["TASK_ID"] = $arFields['TASK_ID'];
			if (array_key_exists("MINUTES", $arFields))
				$update["MINUTES"] = $arFields['MINUTES'];
			if (array_key_exists("SECONDS", $arFields))
				$update["SECONDS"] = $arFields['SECONDS'];
			if (array_key_exists("SOURCE", $arFields))
				$update["SOURCE"] = $arFields['SOURCE'];
			if (array_key_exists("COMMENT_TEXT", $arFields))
				$update["COMMENT_TEXT"] = $arFields['COMMENT_TEXT'];
			$updateResult = \Bitrix\Tasks\Internals\Task\ElapsedTimeTable::update($ID, $update);

			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = ($executiveUserId ? $executiveUserId : 1);

			$oLog = new CTaskLog();
			$oLog->Add(array(
				'TASK_ID'       =>  $taskId,
				'USER_ID'       =>  $occurAsUserId,
				'~CREATED_DATE' =>  $DB->currentTimeFunction(),
				'FIELD'         => 'TIME_SPENT_IN_LOGS',
				'FROM_VALUE'    =>  $curDuration,
				'TO_VALUE'      =>  $curDuration - (int) $arUpdatingLogItem['SECONDS'] + (int) $arFields['SECONDS']
			));

			foreach(GetModuleEvents('tasks', 'OnTaskElapsedTimeUpdate', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			return $updateResult;
		}

		return false;
	}


	public static function Delete($ID, $arParams = array())
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		$executiveUserId = null;
		if (isset($arParams['USER_ID']))
			$executiveUserId = (int) $arParams['USER_ID'];
		elseif ($userId = \Bitrix\Tasks\Util\User::getId())
			$executiveUserId = $userId;

		/** @noinspection PhpDeprecationInspection */
		$rsRemovingLogItem = self::getByID($ID);
		if ($rsRemovingLogItem && ($arRemovingLogItem = $rsRemovingLogItem->fetch()))
			$taskId = $arRemovingLogItem['TASK_ID'];
		else
			return (false);

		$curDuration = 0;
			$rsTask = CTasks::getList(
				array(),
				array('ID' => $taskId),
				array('ID', 'TIME_SPENT_IN_LOGS')
			);
		if ($rsTask && ($arTask = $rsTask->fetch()))
			$curDuration = (int) $arTask['TIME_SPENT_IN_LOGS'];

		foreach(GetModuleEvents('tasks', 'OnBeforeTaskElapsedTimeDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID, $arRemovingLogItem))===false)
				return false;
		}

		$deleteResult = \Bitrix\Tasks\ElapsedTimeTable::delete($ID);

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if ( ! $occurAsUserId )
			$occurAsUserId = ($executiveUserId ? $executiveUserId : 1);

		$oLog = new CTaskLog();
		$oLog->Add(array(
			'TASK_ID'       =>  $taskId,
			'USER_ID'       =>  $occurAsUserId,
			'~CREATED_DATE' =>  $DB->currentTimeFunction(),
			'FIELD'         => 'TIME_SPENT_IN_LOGS',
			'FROM_VALUE'    =>  $curDuration,
			'TO_VALUE'      =>  $curDuration - (int) $arRemovingLogItem['SECONDS']
		));

		foreach(GetModuleEvents('tasks', 'OnTaskElapsedTimeDelete', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, &$arRemovingLogItem));

		return $deleteResult;
	}


	private static function GetFilter($arFilter)
	{
		global $DB;

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
				case "CREATED_DATE":
				case "DATE_START":
				case "DATE_STOP":
					$arSqlSearch[] = CTasks::FilterCreate("TE.".$key, \Bitrix\Tasks\Util\Db::charToDateFunction($val), "date", $bFullJoin, $cOperationType);
					break;

				case "ID":
				case "USER_ID":
				case "TASK_ID":
				case "SOURCE":
					$arSqlSearch[] = CTasks::FilterCreate("TE.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "FIELD":
					$arSqlSearch[] = CTasks::FilterCreate("TE.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}


	public static function GetList($arOrder, $arFilter, $arParams = array())
	{
		global $DB;

		/** @noinspection PhpDeprecationInspection */
		$arSqlSearch = static::GetFilter($arFilter);

		if (isset($arParams['skipJoinUsers']) && $arParams['skipJoinUsers'])
			$bJoinUsers = false;
		else
			$bJoinUsers = true;

		$strSql = "
			SELECT
				TE.*,
				" . $DB->DateToCharFunction("TE.CREATED_DATE", "FULL") . " AS CREATED_DATE, "
				. $DB->DateToCharFunction("TE.DATE_START", "FULL") . " AS DATE_START, "
				. $DB->DateToCharFunction("TE.DATE_STOP", "FULL") . " AS DATE_STOP ";

		if ($bJoinUsers)
		{
			$strSql .= " ,
				U.NAME AS USER_NAME,
				U.LAST_NAME AS USER_LAST_NAME,
				U.SECOND_NAME AS USER_SECOND_NAME,
				U.LOGIN AS USER_LOGIN ";
		}

		$strSql .= "
			FROM
				b_tasks_elapsed_time TE ";

		if ($bJoinUsers)
		{
			$strSql .= " INNER JOIN
				b_user U
			ON
				U.ID = TE.USER_ID
			";
		}

		if ( ! empty($arSqlSearch) )
			$strSql .= "WHERE " . implode(" AND ", $arSqlSearch);

		if (!is_array($arOrder))
			$arOrder = array("CREATED_DATE" => "ASC");

		if ( ! empty($arOrder) )
		{
			foreach ($arOrder as $by => $order)
			{
				$by = mb_strtolower($by);
				$order = mb_strtolower($order);
				if ($order != "asc")
					$order = "desc";

				if ($by == "id")
					$arSqlOrder[] = " TE.ID ".$order." ";
				elseif ($by == "user" || $by == "user_id")
					$arSqlOrder[] = " TE.USER_ID ".$order." ";
				elseif ($by == "field")
					$arSqlOrder[] = " TE.FIELD ".$order." ";
				elseif ($by == "minutes")
					$arSqlOrder[] = " TE.MINUTES ".$order." ";
				elseif ($by == "seconds")
					$arSqlOrder[] = " TE.SECONDS ".$order." ";
				elseif ($by == "rand")
					$arSqlOrder[] = CTasksTools::getRandFunction();
				else
					$arSqlOrder[] = " TE.CREATED_DATE ".$order." ";
			}

			DelDuplicateSort($arSqlOrder);
			$strSql .= " ORDER BY " . implode(', ', $arSqlOrder);
		}

		return $DB->Query($strSql);
	}


	public static function GetByID($ID)
	{
		/** @noinspection PhpDeprecationInspection */
		return CTaskElapsedTime::GetList(array(), array("ID" => $ID));
	}


	function CanCurrentUserAdd($task)
	{
		$userId = \Bitrix\Tasks\Util\User::getId();

		if (!$userID = $userId)
		{
			return false;
		}
		elseif (
			\Bitrix\Tasks\Util\User::isAdmin()
			|| CTasksTools::IsPortalB24Admin()
			|| ($userID == $task["RESPONSIBLE_ID"])
			|| (is_array($task["ACCOMPLICES"]) && in_array($userId, $task["ACCOMPLICES"]))
		)
		{
			return true;
		}

		return false;
	}
}