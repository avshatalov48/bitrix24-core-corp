<?php
IncludeModuleLangFile(__FILE__);

class CControllerTask
{
	public static function GetTaskArray()
	{
		return array(
			'SET_SETTINGS' => GetMessage("CTRLR_TASK_TYPE_SET_SETTINGS"),
			'UPDATE' => GetMessage("CTRLR_TASK_TYPE_UPDATE"),
			'COUNTERS_UPDATE' => GetMessage("CTRLR_TASK_TYPE_COUNTERS_UPDATE"),
			'REMOTE_COMMAND' => GetMessage("CTRLR_TASK_TYPE_REMOTE_COMMAND"),
			'CLOSE_MEMBER' => GetMessage("CTRLR_TASK_TYPE_CLOSE_MEMBER"),
		);
	}

	public static function GetStatusArray()
	{
		return array(
			'N' => GetMessage("CTRLR_TASK_STATUS_NEW"),
			'L' => GetMessage("CTRLR_TASK_STATUS_LOW"),
			'P' => GetMessage("CTRLR_TASK_STATUS_PART"),
			'R' => GetMessage("CTRLR_TASK_STATUS_RETRY"),
			'Y' => GetMessage("CTRLR_TASK_STATUS_COMPL"),
			'F' => GetMessage("CTRLR_TASK_STATUS_FAIL"),
		);
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $DB;

		$arMsg = array();

		if ($ID > 0)
		{
			unset($arFields["ID"]);
		}

		if (($ID === false || is_set($arFields, "TASK_ID")) && $arFields["TASK_ID"] == '')
		{
			$arMsg[] = array("id"=>"TASK_ID", "text"=> GetMessage("CTRLR_TASK_ERR_ID"));
		}
		elseif (is_set($arFields, "TASK_ID"))
		{
			$arTaskID = CControllerTask::GetTaskArray();
			if (!isset($arTaskID[$arFields['TASK_ID']]))
				$arMsg[] = array("id"=>"TASK_ID", "text"=> GetMessage("CTRLR_TASK_ERR_BAD_ID"));
		}

		if (($ID === false || is_set($arFields, "CONTROLLER_MEMBER_ID")) && intval($arFields["CONTROLLER_MEMBER_ID"]) <= 0)
		{
			$arMsg[] = array("id"=>"CONTROLLER_MEMBER_ID", "text"=> GetMessage("CTRLR_TASK_ERR_CLIENTID"));
		}

		if (isset($arFields["INIT_EXECUTE"]))
		{
			$arFields["INIT_CRC"] = crc32($arFields["INIT_EXECUTE"]);
		}

		if (!$arMsg && $ID === false)
		{
			$strSql = "
				SELECT INIT_EXECUTE
				FROM b_controller_task
				WHERE CONTROLLER_MEMBER_ID='".intval($arFields["CONTROLLER_MEMBER_ID"])."'
				AND TASK_ID='".$DB->ForSQL($arFields["TASK_ID"], 255)."'
				AND DATE_EXECUTE IS NULL
			";
			$dbr = $DB->Query($strSql);
			while($ar = $dbr->Fetch())
			{
				if ($ar["INIT_EXECUTE"] == $arFields["INIT_EXECUTE"])
				{
					$arMsg[] = array("id"=>"TASK_ID", "text"=> GetMessage("CTRLR_TASK_ERR_ALREADY")." [".intval($arFields["CONTROLLER_MEMBER_ID"])."].");
					break;
				}
			}
		}

		$APPLICATION->ResetException();
		if ($ID === false)
		{
			foreach (GetModuleEvents("controller", "OnBeforeTaskAdd", true) as $arEvent)
			{
				$bEventRes = ExecuteModuleEventEx($arEvent, array($arFields));
				if ($bEventRes === false)
				{
					if ($err = $APPLICATION->GetException())
					{
						$arMsg[] = array(
							"id" => "ID",
							"text" => $err->GetString()." [".intval($arFields["CONTROLLER_MEMBER_ID"])."].",
						);
					}
					else
					{
						$arMsg[] = array(
							"id" => "ID",
							"text" => "Unknown error."." [".intval($arFields["CONTROLLER_MEMBER_ID"])."].",
						);
					}
					break;
				}
			}
		}

		if ($arMsg)
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		if ($ID === false && !is_set($arFields, "DATE_CREATE"))
		{
			$arFields["~DATE_CREATE"] = $DB->CurrentTimeFunction();
		}

		if ($ID === false && !is_set($arFields, "RETRY_COUNT"))
		{
			$arFields["RETRY_COUNT"] = COption::GetOptionInt("controller", "task_retry_count");
		}

		if ($ID === false && !is_set($arFields, "RETRY_TIMEOUT"))
		{
			$arFields["RETRY_TIMEOUT"] = COption::GetOptionInt("controller", "task_retry_timeout");
		}

		return true;
	}

	public static function Add($arFields)
	{
		global $DB;

		if (!CControllerTask::CheckFields($arFields))
		{
			return false;
		}

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$ID = $DB->Add("b_controller_task", $arFields, array("INIT_EXECUTE", "INIT_EXECUTE_PARAMS"));

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		if (!CControllerTask::CheckFields($arFields, $ID))
		{
			return false;
		}

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$arUpdateBinds = array();
		$strUpdate = $DB->PrepareUpdateBind("b_controller_task", $arFields, "", false, $arUpdateBinds);

		$strSql = "UPDATE b_controller_task SET ".$strUpdate." WHERE ID=".intval($ID);

		$arBinds = array();
		foreach($arUpdateBinds as $field_id)
			$arBinds[$field_id] = $arFields[$field_id];

		$DB->QueryBind($strSql, $arBinds);

		return true;
	}

	public static function Delete($ID)
	{
		global $DB;

		$DB->Query("DELETE FROM b_controller_task WHERE ID=".intval($ID));

		return true;
	}

	public static function GetList($arOrder = Array(), $arFilter = Array(), $bCnt = false, $arNavParams = false)
	{
		global $DB;

		static $arFields = array(
			"ID" => array("FIELD_NAME" => "T.ID", "FIELD_TYPE" => "int"),
			"TIMESTAMP_X" => array("FIELD_NAME" => "T.TIMESTAMP_X", "FIELD_TYPE" => "datetime"),
			"DATE_CREATE" => array("FIELD_NAME" => "T.DATE_CREATE", "FIELD_TYPE" => "datetime"),
			"TASK_ID" => array("FIELD_NAME" => "T.TASK_ID", "FIELD_TYPE" => "string"),
			"CONTROLLER_MEMBER_ID" => array("FIELD_NAME" => "T.CONTROLLER_MEMBER_ID", "FIELD_TYPE" => "int"),
			"CONTROLLER_MEMBER_NAME" => array("FIELD_NAME" => "M.NAME", "FIELD_TYPE" => "string"),
			"CONTROLLER_MEMBER_URL" => array("FIELD_NAME" => "M.URL", "FIELD_TYPE" => "string"),
			"STATUS" => array("FIELD_NAME" => "T.STATUS", "FIELD_TYPE" => "string"),
			"DATE_EXECUTE" => array("FIELD_NAME" => "T.DATE_EXECUTE", "FIELD_TYPE" => "datetime"),
		);

		$obWhere = new CSQLWhere;
		$obWhere->SetFields($arFields);

		$arFilterNew = Array();
		foreach ($arFilter as $k=>$value)
		{
			if (is_array($value) || $value <> '' || $value === false)
			{
				$arFilterNew[$k] = $value;
			}
		}

		$strWhere = $obWhere->GetQuery($arFilterNew);

		if($bCnt)
		{
			$strSelect = "
				SELECT
					COUNT('x') as C
					,MIN(T.ID) as MIN_ID
					,MAX(T.ID) as MAX_ID
			";
		}
		else
		{
			$strSelect = "
				SELECT
					T.ID
					,T.TASK_ID
					,T.CONTROLLER_MEMBER_ID
					,T.INIT_EXECUTE
					,T.INIT_EXECUTE_PARAMS
					,T.INIT_CRC
					,T.UPDATE_PERIOD
					,T.RESULT_EXECUTE
					,T.STATUS
					,T.RETRY_COUNT
					,T.RETRY_TIMEOUT
					,M.NAME as CONTROLLER_MEMBER_NAME
					,M.URL as CONTROLLER_MEMBER_URL
					,".$DB->DateToCharFunction("T.TIMESTAMP_X")." as TIMESTAMP_X
					,".$DB->DateToCharFunction("T.DATE_EXECUTE")." as DATE_EXECUTE
					,".$DB->DateToCharFunction("T.DATE_CREATE")." as DATE_CREATE
					,unix_timestamp(now()) - unix_timestamp(T.DATE_EXECUTE) as EXECUTED_INTERVAL
			";
		}

		$strSql = "
			FROM b_controller_task T
			INNER JOIN b_controller_member M ON T.CONTROLLER_MEMBER_ID = M.ID
			".($strWhere == '' ? "" : "WHERE ".$strWhere)."
		";

		$strOrder = CControllerAgent::_OrderBy($arOrder, $arFields);

		if (is_array($arNavParams) && $arNavParams["nTopCount"] > 0)
		{
			$strSql = $DB->TopSQL($strSelect.$strSql.$strOrder, $arNavParams["nTopCount"]);
			$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		elseif (is_array($arNavParams))
		{
			$res_cnt = $DB->Query("SELECT count('x') CNT ".$strSql);
			$ar_cnt = $res_cnt->Fetch();

			$dbr = new CDBResult();
			$dbr->NavQuery($strSelect.$strSql.$strOrder, $ar_cnt["CNT"], $arNavParams);
		}
		else
		{
			$dbr = $DB->Query($strSelect.$strSql.$strOrder, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$dbr->is_filtered = ($strWhere <> '');

		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CControllerTask::GetList(Array(), Array("ID"=>intval($ID)));
	}

	public static function GetArrayByID($ID)
	{
		$db_task = static::GetByID($ID);
		return $db_task->Fetch();
	}

	public static function ProcessTask($ID)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		/** @global CDatabase $DB */
		global $DB;

		$ID = intval($ID);
		$lockId = "X".$APPLICATION->GetServerUniqID()."_ctask_".$ID;
		$STATUS = "0";

		// locking the task
		if (!CControllerAgent::_Lock($lockId))
		{
			$APPLICATION->ResetException();
			return $STATUS;
		}

		// selecting task
		$strSql =
			"SELECT T.*, M.SHARED_KERNEL ".
			"FROM b_controller_task T LEFT JOIN b_controller_member M ON T.CONTROLLER_MEMBER_ID=M.ID ".
			"WHERE T.ID='".$ID."' AND T.STATUS<>'Y'";

		$db_task = $DB->Query($strSql);
		if ($ar_task = $db_task->Fetch())
		{
			$arControllerLog = array(
				'CONTROLLER_MEMBER_ID' => $ar_task["CONTROLLER_MEMBER_ID"],
				'TASK_ID' => $ar_task['ID'],
				'STATUS' => 'Y',
			);
			$RESULT = '';
			$STATUS = 'Y';
			unset($INIT_EXECUTE_PARAMS);
			$APPLICATION->ResetException();
			switch($ar_task['TASK_ID'])
			{
			case 'SET_SETTINGS':
				$arControllerLog['NAME'] = 'SET_SETTINGS';
				$res = CControllerMember::SetGroupSettings($ar_task["CONTROLLER_MEMBER_ID"], $ar_task['ID']);
				if ($res === false)
				{
					$e = $APPLICATION->GetException();
					$STATUS = "F";
					$RESULT = $e->GetString();
					$arControllerLog['STATUS'] = 'N';
				}
				else
				{
					$RESULT = $res;
				}

				break;
			case 'CLOSE_MEMBER':
				$arControllerLog['NAME'] = 'SITE_CLOSING';
				$res = CControllerMember::CloseMember($ar_task["CONTROLLER_MEMBER_ID"], $ar_task['INIT_EXECUTE_PARAMS'], $ar_task['ID']);
				if ($res === false)
				{
					$STATUS = "F";
					$e = $APPLICATION->GetException();
					if ($e)
						$RESULT = $e->GetString();
				}
				else
				{
					$RESULT = $res;
				}

				break;
			case 'UPDATE':
				$arControllerLog['NAME'] = 'SITE_UPDATE';
				if($ar_task["SHARED_KERNEL"] == "Y")
				{
					$STATUS = "F";
					$RESULT = GetMessage("CTRLR_TASK_ERR_KERNEL");
					$arControllerLog['STATUS'] = 'N';
				}
				else
				{
					$command = 'require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");';
					if($ar_task["STATUS"]=="P" && $ar_task["INIT_EXECUTE_PARAMS"] <> '')
						$command .= 'echo trim(CUpdateControllerSupport::Update("'.EscapePHPString($ar_task["INIT_EXECUTE_PARAMS"]).'"));';
					else
						$command .= 'echo trim(CUpdateControllerSupport::Update(""));';

					$res = CControllerMember::RunCommand($ar_task["CONTROLLER_MEMBER_ID"], $command, array(), $ar_task['ID']);
					if($res!==false)
					{
						if(($p = mb_strpos($res, "|"))>0)
						{
							$result_code = mb_substr($res, 0, $p);
							$RESULT = mb_substr($res, $p + 1);
						}
						else
						{
							$result_code = $res;
							$RESULT = $res;
						}

						if($result_code=='ERR')
						{
							$STATUS = "F";
							$arControllerLog['STATUS'] = 'N';
						}
						elseif($result_code=='STP0') // STP
						{
							$STATUS = "P";
						}
						elseif($result_code!='FIN') // other command
						{
							$STATUS = "P";
							$INIT_EXECUTE_PARAMS = $result_code;
						}
						else
						{
							$RESULT = GetMessage("CTRLR_TASK_UPD_COMPL");
						}
					}
					else
					{
						$STATUS = "F";
						$e = $APPLICATION->GetException();
						$RESULT = $e->GetString();
						$arControllerLog['STATUS'] = 'N';
					}
				}

				break;
			case 'COUNTERS_UPDATE':
				$arControllerLog['NAME'] = 'UPDATE_COUNTERS';
				$res = CControllerMember::UpdateCounters($ar_task["CONTROLLER_MEMBER_ID"], $ar_task['ID']);
				$RESULT = '';
				if($res!==false)
				{
					foreach($res as $k=>$v)
						$RESULT .= "$k=$v;\r\n";
				}
				else
				{
					$e = $APPLICATION->GetException();
					$STATUS = "F";
					$RESULT = $e->GetString();
					$arControllerLog['STATUS'] = 'N';
				}

				break;
			case 'REMOTE_COMMAND':
				$arControllerLog['NAME'] = 'REMOTE_COMMAND';
				if($ar_task['INIT_EXECUTE_PARAMS'] <> '')
					$ar_task['INIT_EXECUTE_PARAMS'] = unserialize($ar_task['INIT_EXECUTE_PARAMS'], ["allowed_classes" => false]);
				else
					$ar_task['INIT_EXECUTE_PARAMS'] = Array();

				//Command was saved in another task record (for db size optimization)
				if ($ar_task['INIT_EXECUTE'] === ''.intval($ar_task['INIT_EXECUTE']).'')
				{
					if($source_task = static::GetArrayByID($ar_task['INIT_EXECUTE']))
					{
						$ar_task['INIT_EXECUTE'] = $source_task['INIT_EXECUTE'];
					}
					else
					{
						$STATUS = "F";
						$RESULT = "Task ID ".intval($ar_task['INIT_EXECUTE'])." not found.";
						$arControllerLog['STATUS'] = 'N';
						break;
					}
				}

				$res = CControllerMember::RunCommand($ar_task["CONTROLLER_MEMBER_ID"], $ar_task['INIT_EXECUTE'], $ar_task['INIT_EXECUTE_PARAMS'], $ar_task['ID'], 'run_immediate');
				if ($res !== false)
				{
					$RESULT = $res;
				}
				else
				{
					$STATUS = "F";
					$e = $APPLICATION->GetException();
					$RESULT = $e->GetString();
					$arControllerLog['STATUS'] = 'N';
				}

				break;
			case 'SEND_FILE':
				$arControllerLog['NAME'] = 'SEND_FILE';

				break;
			}

			if(!isset($arControllerLog['DESCRIPTION']))
				$arControllerLog['DESCRIPTION'] = $RESULT;

			CControllerLog::Add($arControllerLog);

			// updating status
			$arUpdateFields = array(
				"STATUS" => $STATUS,
				"~DATE_EXECUTE" => $DB->CurrentTimeFunction(),
				"RESULT_EXECUTE" => $RESULT,
				"INDEX_SALT" => rand(),
			);
			if(isset($INIT_EXECUTE_PARAMS))
				$arUpdateFields["INIT_EXECUTE_PARAMS"] = $INIT_EXECUTE_PARAMS;

			$arUpdateBinds = array();
			$strUpdate = $DB->PrepareUpdateBind("b_controller_task", $arUpdateFields, "", false, $arUpdateBinds);

			$strSql = "UPDATE b_controller_task SET ".$strUpdate." WHERE ID=".$ID;

			$arBinds = array();
			foreach($arUpdateBinds as $field_id)
				$arBinds[$field_id] = $arUpdateFields[$field_id];

			$DB->QueryBind($strSql, $arBinds);
		}

		// unlocking
		CControllerAgent::_UnLock($lockId);
		return $STATUS;
	}

	public static function PostponeTask($ID, $RETRY_COUNT)
	{
		global $DB;
		$DB->Query("
			UPDATE b_controller_task SET
				RETRY_COUNT=".intval($RETRY_COUNT)."
				".($RETRY_COUNT > 0? ",STATUS='R'": "")."
			WHERE ID=".intval($ID)."
		");
	}

	public static function ProcessAllTask($limit = 10000)
	{
		global $DB;
		//1. Finish partial
		//2. Execute new tasks
		//3. Retry failed tasks
		//4. Run low priority tasks
		foreach (array('P', 'N', 'R', 'L') as $status)
		{
			if ($limit > 0)
			{
				$dbrTask = $DB->Query($DB->TopSQL("
					SELECT ID, RETRY_COUNT
					FROM b_controller_task
					WHERE STATUS = '$status'
					".($status=='R'? 'and DATE_EXECUTE < date_sub(now(), interval RETRY_TIMEOUT second)': '')."
					ORDER BY ID ASC
				", $limit));
				while ($arTask = $dbrTask->Fetch())
				{
					$new_status = CControllerTask::ProcessTask($arTask["ID"]);
					while ($new_status === "P")
					{
						$new_status = CControllerTask::ProcessTask($arTask["ID"]);
					}

					if ($new_status === "F" && $arTask["RETRY_COUNT"] > 0)
					{
						CControllerTask::PostponeTask($arTask["ID"], $arTask["RETRY_COUNT"]-1);
					}

					$limit--;
					if ($limit <= 0)
					{
						break;
					}
				}
			}
		}

		return true;
	}
}
