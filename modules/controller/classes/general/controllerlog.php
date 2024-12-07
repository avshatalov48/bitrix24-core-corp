<?php
IncludeModuleLangFile(__FILE__);

class CControllerLog
{
	public static function GetNameArray()
	{
		if (ControllerIsSharedMode())
		{
			return [
				'REMOTE_COMMAND' => GetMessage('CTRLR_LOG_TYPE_REMOTE_COMMAND'),
				'SET_SETTINGS' => GetMessage('CTRLR_LOG_TYPE_SET_SETTINGS'),
				'SITE_UPDATE' => GetMessage('CTRLR_LOG_TYPE_SITE_UPDATE'),
				'REGISTRATION' => GetMessage('CTRLR_LOG_TYPE_REGISTRATION'),
				'UNREGISTRATION' => GetMessage('CTRLR_LOG_TYPE_UNREGISTRATION'),
				'SITE_UPDATE_KERNEL' => GetMessage('CTRLR_LOG_TYPE_SITE_UPDATE_KERNEL'),
				'SITE_UPDATE_KERNEL_DB' => GetMessage('CTRLR_LOG_TYPE_SITE_UPDATE_KERNEL_DB'),
				'UPDATE_COUNTERS' => GetMessage('CTRLR_LOG_TYPE_UPDATE_COUNTERS'),
				'AUTH' => GetMessage('CTRLR_LOG_TYPE_AUTH'),
				'SITE_CLOSING' => GetMessage('CTRLR_LOG_TYPE_SITE_CLOSE'),
			];
		}
		else
		{
			return [
				'REMOTE_COMMAND' => GetMessage('CTRLR_LOG_TYPE_REMOTE_COMMAND'),
				'SET_SETTINGS' => GetMessage('CTRLR_LOG_TYPE_SET_SETTINGS'),
				'SITE_UPDATE' => GetMessage('CTRLR_LOG_TYPE_SITE_UPDATE'),
				'REGISTRATION' => GetMessage('CTRLR_LOG_TYPE_REGISTRATION'),
				'UNREGISTRATION' => GetMessage('CTRLR_LOG_TYPE_UNREGISTRATION'),
				'UPDATE_COUNTERS' => GetMessage('CTRLR_LOG_TYPE_UPDATE_COUNTERS'),
				'AUTH' => GetMessage('CTRLR_LOG_TYPE_AUTH'),
				'SITE_CLOSING' => GetMessage('CTRLR_LOG_TYPE_SITE_CLOSE'),
			];
		}
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		/** @var CMain $APPLICATION */
		global $APPLICATION;

		$arMsg = [];

		if ($ID > 0)
		{
			unset($arFields['ID']);
		}

		if (($ID === false || array_key_exists('NAME', $arFields)) && $arFields['NAME'] == '')
		{
			$arMsg[] = [
				'id' => 'NAME',
				'text' => GetMessage('CTRLR_LOG_ERR_NAME'),
			];
		}

		if (($ID === false || array_key_exists('CONTROLLER_MEMBER_ID', $arFields)) && intval($arFields['CONTROLLER_MEMBER_ID']) <= 0)
		{
			if (array_key_exists('NAME', $arFields) && $arFields['NAME'] == 'SITE_UPDATE_KERNEL')
			{
				$arFields['CONTROLLER_MEMBER_ID'] = 0;
			}
			else
			{
				$arMsg[] = [
					'id' => 'CONTROLLER_MEMBER_ID',
					'text' => GetMessage('CTRLR_LOG_ERR_UID'),
				];
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

	public static function Add($arFields)
	{
		/** @var CDatabase $DB */
		global $DB;
		/** @var CUser $USER */
		global $USER;

		if (!CControllerLog::CheckFields($arFields))
		{
			return false;
		}

		if (!isset($arFields['USER_ID']) && is_object($USER))
		{
			$arFields['USER_ID'] = $USER->GetID();
		}

		unset($arFields['TIMESTAMP_X']);
		$arFields['~TIMESTAMP_X'] = CDatabase::CurrentTimeFunction();

		$ID = $DB->Add('b_controller_log', $arFields, ['DESCRIPTION']);

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		/** @var CDatabase $DB */
		global $DB;

		if (!CControllerLog::CheckFields($arFields, $ID))
		{
			return false;
		}

		unset($arFields['TIMESTAMP_X']);
		$arFields['~TIMESTAMP_X'] = CDatabase::CurrentTimeFunction();

		$arUpdateBinds = [];
		$strUpdate = $DB->PrepareUpdateBind('b_controller_log', $arFields, '', false, $arUpdateBinds);

		$strSql = 'UPDATE b_controller_log SET ' . $strUpdate . ' WHERE ID=' . intval($ID);

		$arBinds = [];
		foreach ($arUpdateBinds as $field_id)
		{
			$arBinds[$field_id] = $arFields[$field_id];
		}

		$DB->QueryBind($strSql, $arBinds);

		return true;
	}

	public static function Delete($ID)
	{
		/** @var CDatabase $DB */
		global $DB;
		$ID = intval($ID);
		$DB->Query('DELETE FROM b_controller_log WHERE ID=' . $ID);

		return true;
	}

	public static function GetList($arOrder = [], $arFilter = [], $arNavParams = false, $arSelect = [])
	{
		/** @var CDatabase $DB */
		global $DB;

		if (!$arSelect)
		{
			$arSelect = ['ID', 'CONTROLLER_MEMBER_ID', 'NAME', 'DESCRIPTION', 'TASK_ID', 'USER_ID', 'STATUS', 'CONTROLLER_MEMBER_NAME', 'CONTROLLER_MEMBER_URL', 'USER_NAME', 'USER_LAST_NAME', 'USER_LOGIN', 'TASK_NAME', 'TIMESTAMP_X'];
		}

		static $arFields = [
			'ID' => [
				'FIELD_NAME' => 'L.ID',
				'FIELD_TYPE' => 'int',
			],
			'TIMESTAMP_X' => [
				'FIELD_NAME' => 'L.TIMESTAMP_X',
				'FIELD_TYPE' => 'datetime',
			],
			'CONTROLLER_MEMBER_ID' => [
				'FIELD_NAME' => 'L.CONTROLLER_MEMBER_ID',
				'FIELD_TYPE' => 'int',
			],
			'CONTROLLER_MEMBER_NAME' => [
				'FIELD_NAME' => 'M.NAME',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'M',
				'JOIN' => 'INNER JOIN b_controller_member M ON M.ID = L.CONTROLLER_MEMBER_ID',
				'LEFT_JOIN' => 'LEFT JOIN b_controller_member M ON M.ID = L.CONTROLLER_MEMBER_ID',
			],
			'CONTROLLER_MEMBER_URL' => [
				'FIELD_NAME' => 'M.URL',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'M',
				'JOIN' => 'INNER JOIN b_controller_member M ON M.ID = L.CONTROLLER_MEMBER_ID',
				'LEFT_JOIN' => 'LEFT JOIN b_controller_member M ON M.ID = L.CONTROLLER_MEMBER_ID',
			],
			'NAME' => [
				'FIELD_NAME' => 'L.NAME',
				'FIELD_TYPE' => 'string',
			],
			'DESCRIPTION' => [
				'FIELD_NAME' => 'L.DESCRIPTION',
				'FIELD_TYPE' => 'string',
			],
			'TASK_ID' => [
				'FIELD_NAME' => 'L.TASK_ID',
				'FIELD_TYPE' => 'int',
			],
			'TASK_NAME' => [
				'FIELD_NAME' => 'T.TASK_ID',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'T',
				'JOIN' => 'INNER JOIN b_controller_task T ON T.ID = L.TASK_ID',
				'LEFT_JOIN' => 'LEFT JOIN b_controller_task T ON T.ID = L.TASK_ID',
			],
			'USER_ID' => [
				'FIELD_NAME' => 'L.USER_ID',
				'FIELD_TYPE' => 'int',
			],
			'USER_NAME' => [
				'FIELD_NAME' => 'U.NAME',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'U',
				'JOIN' => 'INNER JOIN b_user U ON U.ID = L.USER_ID',
				'LEFT_JOIN' => 'LEFT JOIN b_user U ON U.ID = L.USER_ID',
			],
			'USER_LAST_NAME' => [
				'FIELD_NAME' => 'U.LAST_NAME',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'U',
				'JOIN' => 'INNER JOIN b_user U ON U.ID = L.USER_ID',
				'LEFT_JOIN' => 'LEFT JOIN b_user U ON U.ID = L.USER_ID',
			],
			'USER_LOGIN' => [
				'FIELD_NAME' => 'U.LOGIN',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'U',
				'JOIN' => 'INNER JOIN b_user U ON U.ID = L.USER_ID',
				'LEFT_JOIN' => 'LEFT JOIN b_user U ON U.ID = L.USER_ID',
			],
			'STATUS' => [
				'FIELD_NAME' => 'L.STATUS',
				'FIELD_TYPE' => 'string',
			],
		];

		$obWhere = new CSQLWhere;
		$obWhere->SetFields($arFields);

		$arFilterNew = [];
		foreach ($arFilter as $k => $value)
		{
			if ($value <> '' || $value === false)
			{
				$arFilterNew[$k] = $value;
			}
		}

		$strWhere = trim($obWhere->GetQuery($arFilterNew));

		if (is_array($arOrder))
		{
			foreach ($arOrder as $key => $_)
			{
				$key = mb_strtoupper($key);
				if (array_key_exists($key, $arFields) && isset($arFields[$key]['LEFT_JOIN']))
				{
					$obWhere->c_joins[$key]++;
				}
			}
		}

		$duplicates = ['ID' => 1];
		$strSelect = "SELECT L.ID AS ID\n";
		foreach ($arSelect as $key)
		{
			$key = mb_strtoupper($key);
			if (array_key_exists($key, $arFields) && !array_key_exists($key, $duplicates))
			{
				$duplicates[$key] = 1;

				if (isset($arFields[$key]['LEFT_JOIN']))
				{
					$obWhere->c_joins[$key]++;
				}

				if ($arFields[$key]['FIELD_TYPE'] == 'datetime')
				{
					$strSelect .= ',' . $DB->DateToCharFunction($arFields[$key]['FIELD_NAME'], $arFields[$key]['FORMAT']) . ' AS ' . $key . "\n";
				}
				else
				{
					$strSelect .= ',' . $arFields[$key]['FIELD_NAME'] . ' AS ' . $key . "\n";
				}
			}
		}

		$strSql = '
			FROM b_controller_log L
			' . $obWhere->GetJoins() . '
			' . ($strWhere === '' ? '' : 'WHERE ' . $strWhere) . '
		';

		$strOrder = CControllerAgent::_OrderBy($arOrder, $arFields);

		if (!is_array($arNavParams))
		{
			$dbr = $DB->Query($strSelect . $strSql . $strOrder);
		}
		elseif ($arNavParams['nTopCount'] > 0)
		{
			$strSql = $strSelect . $strSql . $strOrder . "\nLIMIT " . intval($arNavParams['nTopCount']);
			if ($arNavParams['nOffset'] > 0)
			{
				$strSql .= ' OFFSET ' . intval($arNavParams['nOffset']);
			}
			$dbr = $DB->Query($strSql);
		}
		else
		{
			$res_cnt = $DB->Query("SELECT count('x') CNT " . $strSql);
			$ar_cnt = $res_cnt->Fetch();
			if (isset($arNavParams['bOnlyCount']) && $arNavParams['bOnlyCount'] === true)
			{
				return $ar_cnt['CNT'];
			}

			$dbr = new CDBResult();
			$dbr->NavQuery($strSelect . $strSql . $strOrder, $ar_cnt['CNT'], $arNavParams);
		}

		$dbr->is_filtered = ($strWhere !== '');

		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CControllerLog::GetList([], ['ID' => intval($ID)]);
	}
}
