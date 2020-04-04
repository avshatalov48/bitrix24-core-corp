<?php
IncludeModuleLangFile(__FILE__);

class CControllerLog
{
	public static function GetNameArray()
	{
		if (ControllerIsSharedMode())
		{
			return array(
				'REMOTE_COMMAND' => GetMessage("CTRLR_LOG_TYPE_REMOTE_COMMAND"),
				'SET_SETTINGS' => GetMessage("CTRLR_LOG_TYPE_SET_SETTINGS"),
				'SITE_UPDATE' => GetMessage("CTRLR_LOG_TYPE_SITE_UPDATE"),
				'REGISTRATION' => GetMessage("CTRLR_LOG_TYPE_REGISTRATION"),
				'UNREGISTRATION' => GetMessage("CTRLR_LOG_TYPE_UNREGISTRATION"),
				'SITE_UPDATE_KERNEL' => GetMessage("CTRLR_LOG_TYPE_SITE_UPDATE_KERNEL"),
				'SITE_UPDATE_KERNEL_DB' => GetMessage("CTRLR_LOG_TYPE_SITE_UPDATE_KERNEL_DB"),
				'UPDATE_COUNTERS' => GetMessage("CTRLR_LOG_TYPE_UPDATE_COUNTERS"),
				'AUTH' => GetMessage("CTRLR_LOG_TYPE_AUTH"),
				'SITE_CLOSING' => GetMessage("CTRLR_LOG_TYPE_SITE_CLOSE"),
			);
		}
		else
		{
			return array(
				'REMOTE_COMMAND' => GetMessage("CTRLR_LOG_TYPE_REMOTE_COMMAND"),
				'SET_SETTINGS' => GetMessage("CTRLR_LOG_TYPE_SET_SETTINGS"),
				'SITE_UPDATE' => GetMessage("CTRLR_LOG_TYPE_SITE_UPDATE"),
				'REGISTRATION' => GetMessage("CTRLR_LOG_TYPE_REGISTRATION"),
				'UNREGISTRATION' => GetMessage("CTRLR_LOG_TYPE_UNREGISTRATION"),
				'UPDATE_COUNTERS' => GetMessage("CTRLR_LOG_TYPE_UPDATE_COUNTERS"),
				'AUTH' => GetMessage("CTRLR_LOG_TYPE_AUTH"),
				'SITE_CLOSING' => GetMessage("CTRLR_LOG_TYPE_SITE_CLOSE"),
			);
		}
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arMsg = array();

		if ($ID > 0)
		{
			unset($arFields["ID"]);
		}

		if(($ID === false || array_key_exists("NAME", $arFields)) && strlen($arFields["NAME"]) <= 0)
		{
			$arMsg[] = array(
				"id" => "NAME",
				"text" => GetMessage("CTRLR_LOG_ERR_NAME"),
			);
		}

		if(($ID === false || array_key_exists("CONTROLLER_MEMBER_ID", $arFields)) && intval($arFields["CONTROLLER_MEMBER_ID"]) <= 0)
		{
			if(array_key_exists("NAME", $arFields) && $arFields["NAME"] == "SITE_UPDATE_KERNEL")
			{
				$arFields["CONTROLLER_MEMBER_ID"] = 0;
			}
			else
			{
				$arMsg[] = array(
					"id" => "CONTROLLER_MEMBER_ID",
					"text" => GetMessage("CTRLR_LOG_ERR_UID"),
				);
			}
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function Add($arFields)
	{
		/** @global CDatabase $DB */
		global $DB;
		/** @global CUser $USER */
		global $USER;

		if (!CControllerLog::CheckFields($arFields))
		{
			return false;
		}

		if (!isset($arFields["USER_ID"]) && is_object($USER))
		{
			$arFields["USER_ID"] = $USER->GetId();
		}

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$ID = $DB->Add("b_controller_log", $arFields, array("DESCRIPTION"));

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		/** @global CDatabase $DB */
		global $DB;

		if (!CControllerLog::CheckFields($arFields, $ID))
		{
			return false;
		}

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$arUpdateBinds = array();
		$strUpdate = $DB->PrepareUpdateBind("b_controller_log", $arFields, "", false, $arUpdateBinds);

		$strSql = "UPDATE b_controller_log SET ".$strUpdate." WHERE ID=".intval($ID);

		$arBinds = array();
		foreach ($arUpdateBinds as $field_id)
		{
			$arBinds[$field_id] = $arFields[$field_id];
		}

		$DB->QueryBind($strSql, $arBinds);

		return true;
	}

	public static function Delete($ID)
	{
		/** @global CDatabase $DB */
		global $DB;
		$ID = intval($ID);
		$DB->Query("DELETE FROM b_controller_log WHERE ID=".$ID);

		return true;
	}

	public static function GetList($arOrder = Array(), $arFilter = Array(), $arNavParams = false)
	{
		/** @global CDatabase $DB */
		global $DB;

		static $arFields = array(
			"ID" => array(
				"FIELD_NAME" => "L.ID",
				"FIELD_TYPE" => "int",
			),
			"CONTROLLER_MEMBER_ID" => array(
				"FIELD_NAME" => "L.CONTROLLER_MEMBER_ID",
				"FIELD_TYPE" => "int",
			),
			"NAME" => array(
				"FIELD_NAME" => "L.NAME",
				"FIELD_TYPE" => "string",
			),
			"TIMESTAMP_X" => array(
				"FIELD_NAME" => "L.TIMESTAMP_X",
				"FIELD_TYPE" => "datetime",
			),
			"TASK_ID" => array(
				"FIELD_NAME" => "L.TASK_ID",
				"FIELD_TYPE" => "int",
			),
			"TASK_NAME" => array(
				"FIELD_NAME" => "T.TASK_ID",
				"FIELD_TYPE" => "string",
				"JOIN" => "INNER JOIN b_controller_task T ON T.ID = L.TASK_ID",
				"LEFT_JOIN" => "LEFT JOIN b_controller_task T ON T.ID = L.TASK_ID",
			),
			"USER_ID" => array(
				"FIELD_NAME" => "L.USER_ID",
				"FIELD_TYPE" => "int",
			),
			"USER_NAME" => array(
				"FIELD_NAME" => "L.USER_ID",
				"FIELD_TYPE" => "string",
			),
			"USER_LAST_NAME" => array(
				"FIELD_NAME" => "U.LAST_NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => "INNER JOIN b_user U ON U.ID = L.USER_ID",
				"LEFT_JOIN" => "LEFT JOIN b_user U ON U.ID = L.USER_ID",
			),
			"USER_LOGIN" => array(
				"FIELD_NAME" => "U.LOGIN",
				"FIELD_TYPE" => "string",
				"JOIN" => "INNER JOIN b_user U ON U.ID = L.USER_ID",
				"LEFT_JOIN" => "LEFT JOIN b_user U ON U.ID = L.USER_ID",
			),
			"STATUS" => array(
				"FIELD_NAME" => "L.STATUS",
				"FIELD_TYPE" => "string",
			),
			"CONTROLLER_MEMBER_NAME" => array(
				"FIELD_NAME" => "M.NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => "INNER JOIN b_controller_member M ON M.ID = L.CONTROLLER_MEMBER_ID",
				"LEFT_JOIN" => "LEFT JOIN b_controller_member M ON M.ID = L.CONTROLLER_MEMBER_ID",
			),
			"CONTROLLER_MEMBER_URL" => array(
				"FIELD_NAME" => "M.URL",
				"FIELD_TYPE" => "string",
				"JOIN" => "INNER JOIN b_controller_member M ON M.ID = L.CONTROLLER_MEMBER_ID",
				"LEFT_JOIN" => "LEFT JOIN b_controller_member M ON M.ID = L.CONTROLLER_MEMBER_ID",
			),
		);

		$obWhere = new CSQLWhere;
		$obWhere->SetFields($arFields);

		$arFilterNew = array();
		foreach ($arFilter as $k=>$value)
		{
			if (strlen($value)>0 || $value === false)
			{
				$arFilterNew[$k]=$value;
			}
		}

		$strWhere = $obWhere->GetQuery($arFilterNew);

		$strSelect = "
			SELECT
				L.ID
				,L.CONTROLLER_MEMBER_ID
				,L.NAME
				,L.DESCRIPTION
				,L.TASK_ID
				,L.USER_ID
				,L.STATUS
				,M.NAME as CONTROLLER_MEMBER_NAME
				,M.URL as CONTROLLER_MEMBER_URL
				,U.NAME as USER_NAME
				,U.LAST_NAME as USER_LAST_NAME
				,U.LOGIN as USER_LOGIN
				,T.TASK_ID as TASK_NAME
				,".$DB->DateToCharFunction("L.TIMESTAMP_X")." as TIMESTAMP_X
		";

		$strSql = "
			FROM b_controller_log L
			LEFT JOIN b_controller_member M ON L.CONTROLLER_MEMBER_ID=M.ID
			LEFT JOIN b_controller_task T ON T.ID = L.TASK_ID
			LEFT JOIN b_user U ON U.ID = L.USER_ID
			".(strlen($strWhere) <= 0? "": "WHERE ".$strWhere)."
		";

		$strOrder = CControllerAgent::_OrderBy($arOrder, $arFields);

		if (is_array($arNavParams) && $arNavParams["nTopCount"] > 0)
		{
			$strSql = $DB->TopSQL($strSelect.$strSql.$strOrder, $arNavParams["nTopCount"]);
			$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		elseif (is_array($arNavParams))
		{
			$res_cnt = $DB->Query("
				SELECT count('x') CNT
				FROM b_controller_log L
				".$obWhere->GetJoins()."
				".(strlen($strWhere) <= 0? "": "WHERE ".$strWhere)."
			");
			$ar_cnt = $res_cnt->Fetch();

			$dbr = new CDBResult();
			$dbr->NavQuery($strSelect.$strSql.$strOrder, $ar_cnt["CNT"], $arNavParams);
		}
		else
		{
			$dbr = $DB->Query($strSelect.$strSql.$strOrder, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$dbr->is_filtered = (strlen($strWhere) > 0);

		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CControllerLog::GetList(Array(), Array("ID"=>IntVal($ID)));
	}
}
