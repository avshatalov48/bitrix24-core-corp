<?php
IncludeModuleLangFile(__FILE__);

class CControllerTask
{
	public static function GetTaskArray()
	{
		return [
			'SET_SETTINGS' => GetMessage('CTRLR_TASK_TYPE_SET_SETTINGS'),
			'UPDATE' => GetMessage('CTRLR_TASK_TYPE_UPDATE'),
			'COUNTERS_UPDATE' => GetMessage('CTRLR_TASK_TYPE_COUNTERS_UPDATE'),
			'REMOTE_COMMAND' => GetMessage('CTRLR_TASK_TYPE_REMOTE_COMMAND'),
			'CLOSE_MEMBER' => GetMessage('CTRLR_TASK_TYPE_CLOSE_MEMBER'),
		];
	}

	public static function GetStatusArray()
	{
		return [
			'N' => GetMessage('CTRLR_TASK_STATUS_NEW'),
			'L' => GetMessage('CTRLR_TASK_STATUS_LOW'),
			'P' => GetMessage('CTRLR_TASK_STATUS_PART'),
			'R' => GetMessage('CTRLR_TASK_STATUS_RETRY'),
			'Y' => GetMessage('CTRLR_TASK_STATUS_COMPL'),
			'F' => GetMessage('CTRLR_TASK_STATUS_FAIL'),
		];
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		/** @var CMain $APPLICATION */
		global $APPLICATION, $DB;

		$arMsg = [];

		if ($ID > 0)
		{
			unset($arFields['ID']);
		}

		if (($ID === false || is_set($arFields, 'TASK_ID')) && $arFields['TASK_ID'] == '')
		{
			$arMsg[] = ['id' => 'TASK_ID', 'text' => GetMessage('CTRLR_TASK_ERR_ID')];
		}
		elseif (is_set($arFields, 'TASK_ID'))
		{
			$arTaskID = CControllerTask::GetTaskArray();
			if (!isset($arTaskID[$arFields['TASK_ID']]))
			{
				$arMsg[] = ['id' => 'TASK_ID', 'text' => GetMessage('CTRLR_TASK_ERR_BAD_ID')];
			}
		}

		if (($ID === false || is_set($arFields, 'CONTROLLER_MEMBER_ID')) && intval($arFields['CONTROLLER_MEMBER_ID']) <= 0)
		{
			$arMsg[] = ['id' => 'CONTROLLER_MEMBER_ID', 'text' => GetMessage('CTRLR_TASK_ERR_CLIENTID')];
		}

		if (isset($arFields['INIT_EXECUTE']))
		{
			$arFields['INIT_CRC'] = crc32($arFields['INIT_EXECUTE']);
		}

		if (!$arMsg && $ID === false)
		{
			$strSql = "
				SELECT INIT_EXECUTE
				FROM b_controller_task
				WHERE CONTROLLER_MEMBER_ID='" . intval($arFields['CONTROLLER_MEMBER_ID']) . "'
				AND TASK_ID='" . $DB->ForSQL($arFields['TASK_ID'], 255) . "'
				AND DATE_EXECUTE IS NULL
			";
			$dbr = $DB->Query($strSql);
			while ($ar = $dbr->Fetch())
			{
				if ($ar['INIT_EXECUTE'] == $arFields['INIT_EXECUTE'])
				{
					$arMsg[] = ['id' => 'TASK_ID', 'text' => GetMessage('CTRLR_TASK_ERR_ALREADY') . ' [' . intval($arFields['CONTROLLER_MEMBER_ID']) . '].'];
					break;
				}
			}
		}

		$APPLICATION->ResetException();
		if ($ID === false)
		{
			foreach (GetModuleEvents('controller', 'OnBeforeTaskAdd', true) as $arEvent)
			{
				$bEventRes = ExecuteModuleEventEx($arEvent, [$arFields]);
				if ($bEventRes === false)
				{
					if ($err = $APPLICATION->GetException())
					{
						$arMsg[] = [
							'id' => 'ID',
							'text' => $err->GetString() . ' [' . intval($arFields['CONTROLLER_MEMBER_ID']) . '].',
						];
					}
					else
					{
						$arMsg[] = [
							'id' => 'ID',
							'text' => 'Unknown error.' . ' [' . intval($arFields['CONTROLLER_MEMBER_ID']) . '].',
						];
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

		if ($ID === false && !is_set($arFields, 'DATE_CREATE'))
		{
			$arFields['~DATE_CREATE'] = $DB->CurrentTimeFunction();
		}

		if ($ID === false && !is_set($arFields, 'RETRY_COUNT'))
		{
			$arFields['RETRY_COUNT'] = COption::GetOptionInt('controller', 'task_retry_count');
		}

		if ($ID === false && !is_set($arFields, 'RETRY_TIMEOUT'))
		{
			$arFields['RETRY_TIMEOUT'] = COption::GetOptionInt('controller', 'task_retry_timeout');
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

		unset($arFields['TIMESTAMP_X']);
		$arFields['~TIMESTAMP_X'] = $DB->CurrentTimeFunction();

		$ID = $DB->Add('b_controller_task', $arFields, ['INIT_EXECUTE', 'INIT_EXECUTE_PARAMS']);

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		if (!CControllerTask::CheckFields($arFields, $ID))
		{
			return false;
		}

		unset($arFields['TIMESTAMP_X']);
		$arFields['~TIMESTAMP_X'] = $DB->CurrentTimeFunction();

		$arUpdateBinds = [];
		$strUpdate = $DB->PrepareUpdateBind('b_controller_task', $arFields, '', false, $arUpdateBinds);

		$strSql = 'UPDATE b_controller_task SET ' . $strUpdate . ' WHERE ID=' . intval($ID);

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
		global $DB;

		$DB->Query('DELETE FROM b_controller_task WHERE ID=' . intval($ID));

		return true;
	}

	public static function GetList($arOrder = [], $arFilter = [], $bCnt = false, $arNavParams = false)
	{
		global $DB;

		if (is_array($bCnt))
		{
			$arSelect = $bCnt;
			$bCnt = false;
		}
		else
		{
			$arSelect = ['ID'];
		}

		if (!$arSelect)
		{
			$arSelect = ['ID', 'TASK_ID', 'CONTROLLER_MEMBER_ID', 'INIT_EXECUTE', 'INIT_EXECUTE_PARAMS', 'INIT_CRC', 'UPDATE_PERIOD', 'RESULT_EXECUTE', 'STATUS', 'RETRY_COUNT', 'RETRY_TIMEOUT', 'CONTROLLER_MEMBER_NAME', 'CONTROLLER_MEMBER_URL', 'TIMESTAMP_X', 'DATE_EXECUTE', 'DATE_CREATE', 'EXECUTED_INTERVAL'];
		}

		static $arFields = [
			'ID' => [
				'FIELD_NAME' => 'T.ID',
				'FIELD_TYPE' => 'int',
			],
			'TIMESTAMP_X' => [
				'FIELD_NAME' => 'T.TIMESTAMP_X',
				'FIELD_TYPE' => 'datetime',
			],
			'DATE_CREATE' => [
				'FIELD_NAME' => 'T.DATE_CREATE',
				'FIELD_TYPE' => 'datetime',
			],
			'TASK_ID' => [
				'FIELD_NAME' => 'T.TASK_ID',
				'FIELD_TYPE' => 'string',
			],
			'CONTROLLER_MEMBER_ID' => [
				'FIELD_NAME' => 'T.CONTROLLER_MEMBER_ID',
				'FIELD_TYPE' => 'int',
			],
			'CONTROLLER_MEMBER_NAME' => [
				'FIELD_NAME' => 'M.NAME',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'M',
				'JOIN' => 'INNER JOIN b_controller_member M ON M.ID = T.CONTROLLER_MEMBER_ID',
				'LEFT_JOIN' => 'LEFT JOIN b_controller_member M ON M.ID = T.CONTROLLER_MEMBER_ID',
			],
			'CONTROLLER_MEMBER_URL' => [
				'FIELD_NAME' => 'M.URL',
				'FIELD_TYPE' => 'string',
				'TABLE_ALIAS' => 'M',
				'JOIN' => 'INNER JOIN b_controller_member M ON M.ID = T.CONTROLLER_MEMBER_ID',
				'LEFT_JOIN' => 'LEFT JOIN b_controller_member M ON M.ID = T.CONTROLLER_MEMBER_ID',
			],
			'DATE_EXECUTE' => [
				'FIELD_NAME' => 'T.DATE_EXECUTE',
				'FIELD_TYPE' => 'datetime',
			],
			'EXECUTED_INTERVAL' => [
				'FIELD_NAME' => 'unix_timestamp(now()) - unix_timestamp(T.DATE_EXECUTE)',
				'FIELD_TYPE' => 'int',
			],
			'INIT_EXECUTE' => [
				'FIELD_NAME' => 'T.INIT_EXECUTE',
				'FIELD_TYPE' => 'string',
			],
			'INIT_EXECUTE_PARAMS' => [
				'FIELD_NAME' => 'T.INIT_EXECUTE_PARAMS',
				'FIELD_TYPE' => 'string',
			],
			'INIT_CRC' => [
				'FIELD_NAME' => 'T.INIT_CRC',
				'FIELD_TYPE' => 'int',
			],
			'UPDATE_PERIOD' => [
				'FIELD_NAME' => 'T.UPDATE_PERIOD',
				'FIELD_TYPE' => 'int',
			],
			'RESULT_EXECUTE' => [
				'FIELD_NAME' => 'T.RESULT_EXECUTE',
				'FIELD_TYPE' => 'string',
			],
			'STATUS' => [
				'FIELD_NAME' => 'T.STATUS',
				'FIELD_TYPE' => 'string',
			],
			'INDEX_SALT' => [
				'FIELD_NAME' => 'T.INDEX_SALT',
				'FIELD_TYPE' => 'int',
			],
			'RETRY_COUNT' => [
				'FIELD_NAME' => 'T.RETRY_COUNT',
				'FIELD_TYPE' => 'int',
			],
			'RETRY_TIMEOUT' => [
				'FIELD_NAME' => 'T.RETRY_TIMEOUT',
				'FIELD_TYPE' => 'int',
			],
		];

		$obWhere = new CSQLWhere;
		$obWhere->SetFields($arFields);

		$arFilterNew = [];
		foreach ($arFilter as $k => $value)
		{
			if (is_array($value) || $value <> '' || $value === false)
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
		$strSelect = "SELECT T.ID AS ID\n";
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

		if ($bCnt)
		{
			$strSelect = "
				SELECT
					COUNT('x') as C
					,MIN(T.ID) as MIN_ID
					,MAX(T.ID) as MAX_ID
			";
		}

		$strSql = '
			FROM b_controller_task T
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
		return CControllerTask::GetList([], ['ID' => intval($ID)]);
	}

	public static function GetArrayByID($ID)
	{
		$db_task = static::GetByID($ID);
		return $db_task->Fetch();
	}

	public static function ProcessTask($ID)
	{
		/** @var CMain $APPLICATION */
		global $APPLICATION;
		/** @var CDatabase $DB */
		global $DB;
		$connection = \Bitrix\Main\Application::getConnection();

		$ID = intval($ID);
		$lockId = 'X' . CMain::GetServerUniqID() . '_ctask_' . $ID;
		$STATUS = '0';

		// locking the task
		if (!$connection->lock($lockId))
		{
			$APPLICATION->ResetException();
			return $STATUS;
		}

		// selecting task
		$strSql = '
			SELECT T.*, M.SHARED_KERNEL
			FROM b_controller_task T
			LEFT JOIN b_controller_member M ON T.CONTROLLER_MEMBER_ID = M.ID
			WHERE T.ID = ' . $ID . ' AND T.STATUS <> \'Y\'
		';

		$db_task = $DB->Query($strSql);
		if ($ar_task = $db_task->Fetch())
		{
			$arControllerLog = [
				'CONTROLLER_MEMBER_ID' => $ar_task['CONTROLLER_MEMBER_ID'],
				'TASK_ID' => $ar_task['ID'],
				'STATUS' => 'Y',
			];
			$RESULT = '';
			$STATUS = 'Y';
			unset($INIT_EXECUTE_PARAMS);
			$APPLICATION->ResetException();
			switch ($ar_task['TASK_ID'])
			{
			case 'SET_SETTINGS':
				$arControllerLog['NAME'] = 'SET_SETTINGS';
				$res = CControllerMember::SetGroupSettings($ar_task['CONTROLLER_MEMBER_ID'], $ar_task['ID']);
				if ($res === false)
				{
					$e = $APPLICATION->GetException();
					$STATUS = 'F';
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
				$res = CControllerMember::CloseMember($ar_task['CONTROLLER_MEMBER_ID'], $ar_task['INIT_EXECUTE_PARAMS'], $ar_task['ID']);
				if ($res === false)
				{
					$STATUS = 'F';
					$e = $APPLICATION->GetException();
					if ($e)
					{
						$RESULT = $e->GetString();
					}
				}
				else
				{
					$RESULT = $res;
				}

				break;
			case 'UPDATE':
				$arControllerLog['NAME'] = 'SITE_UPDATE';
				if ($ar_task['SHARED_KERNEL'] == 'Y')
				{
					$STATUS = 'F';
					$RESULT = GetMessage('CTRLR_TASK_ERR_KERNEL');
					$arControllerLog['STATUS'] = 'N';
				}
				else
				{
					$command = 'require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");';
					if ($ar_task['STATUS'] == 'P' && $ar_task['INIT_EXECUTE_PARAMS'] <> '')
					{
						$command .= 'echo trim(CUpdateControllerSupport::Update("' . EscapePHPString($ar_task['INIT_EXECUTE_PARAMS']) . '"));';
					}
					else
					{
						$command .= 'echo trim(CUpdateControllerSupport::Update(""));';
					}

					$res = CControllerMember::RunCommand($ar_task['CONTROLLER_MEMBER_ID'], $command, [], $ar_task['ID']);
					if ($res !== false)
					{
						if (($p = mb_strpos($res, '|')) > 0)
						{
							$result_code = mb_substr($res, 0, $p);
							$RESULT = mb_substr($res, $p + 1);
						}
						else
						{
							$result_code = $res;
							$RESULT = $res;
						}

						if ($result_code == 'ERR')
						{
							$STATUS = 'F';
							$arControllerLog['STATUS'] = 'N';
						}
						elseif ($result_code == 'STP0') // STP
						{
							$STATUS = 'P';
						}
						elseif ($result_code != 'FIN') // other command
						{
							$STATUS = 'P';
							$INIT_EXECUTE_PARAMS = $result_code;
						}
						else
						{
							$RESULT = GetMessage('CTRLR_TASK_UPD_COMPL');
						}
					}
					else
					{
						$STATUS = 'F';
						$e = $APPLICATION->GetException();
						$RESULT = $e->GetString();
						$arControllerLog['STATUS'] = 'N';
					}
				}

				break;
			case 'COUNTERS_UPDATE':
				$arControllerLog['NAME'] = 'UPDATE_COUNTERS';
				$res = CControllerMember::UpdateCounters($ar_task['CONTROLLER_MEMBER_ID'], $ar_task['ID']);
				$RESULT = '';
				if ($res !== false)
				{
					foreach ($res as $k => $v)
					{
						$RESULT .= $k . '=' . $v . ";\r\n";
					}
				}
				else
				{
					$e = $APPLICATION->GetException();
					$STATUS = 'F';
					$RESULT = $e->GetString();
					$arControllerLog['STATUS'] = 'N';
				}

				break;
			case 'REMOTE_COMMAND':
				$arControllerLog['NAME'] = 'REMOTE_COMMAND';
				if ($ar_task['INIT_EXECUTE_PARAMS'] <> '')
				{
					$ar_task['INIT_EXECUTE_PARAMS'] = unserialize($ar_task['INIT_EXECUTE_PARAMS'], ['allowed_classes' => false]);
				}
				else
				{
					$ar_task['INIT_EXECUTE_PARAMS'] = [];
				}

				//Command was saved in another task record (for db size optimization)
				if (is_numeric($ar_task['INIT_EXECUTE']))
				{
					if ($source_task = static::GetArrayByID($ar_task['INIT_EXECUTE']))
					{
						$ar_task['INIT_EXECUTE'] = $source_task['INIT_EXECUTE'];
					}
					else
					{
						$STATUS = 'F';
						$RESULT = 'Task ID ' . intval($ar_task['INIT_EXECUTE']) . ' not found.';
						$arControllerLog['STATUS'] = 'N';
						break;
					}
				}

				$res = CControllerMember::RunCommand($ar_task['CONTROLLER_MEMBER_ID'], $ar_task['INIT_EXECUTE'], $ar_task['INIT_EXECUTE_PARAMS'], $ar_task['ID'], 'run_immediate');
				if ($res !== false)
				{
					$RESULT = $res;
				}
				else
				{
					$STATUS = 'F';
					$e = $APPLICATION->GetException();
					$RESULT = $e->GetString();
					$arControllerLog['STATUS'] = 'N';
				}

				break;
			case 'SEND_FILE':
				$arControllerLog['NAME'] = 'SEND_FILE';

				break;
			}

			if (!isset($arControllerLog['DESCRIPTION']))
			{
				$arControllerLog['DESCRIPTION'] = $RESULT;
			}

			CControllerLog::Add($arControllerLog);

			// updating status
			$arUpdateFields = [
				'STATUS' => $STATUS,
				'~DATE_EXECUTE' => CDatabase::CurrentTimeFunction(),
				'RESULT_EXECUTE' => $RESULT,
				'INDEX_SALT' => rand(),
			];
			if (isset($INIT_EXECUTE_PARAMS))
			{
				$arUpdateFields['INIT_EXECUTE_PARAMS'] = $INIT_EXECUTE_PARAMS;
			}

			$arUpdateBinds = [];
			$strUpdate = $DB->PrepareUpdateBind('b_controller_task', $arUpdateFields, '', false, $arUpdateBinds);

			$strSql = 'UPDATE b_controller_task SET ' . $strUpdate . ' WHERE ID=' . $ID;

			$arBinds = [];
			foreach ($arUpdateBinds as $field_id)
			{
				$arBinds[$field_id] = $arUpdateFields[$field_id];
			}

			$DB->QueryBind($strSql, $arBinds);
		}

		// unlocking
		$connection->unlock($lockId);

		return $STATUS;
	}

	public static function PostponeTask($ID, $RETRY_COUNT)
	{
		global $DB;
		$DB->Query('
			UPDATE b_controller_task SET
				RETRY_COUNT=' . intval($RETRY_COUNT) . '
				' . ($RETRY_COUNT > 0 ? ",STATUS='R'" : '') . '
			WHERE ID=' . intval($ID) . '
		');
	}

	public static function ProcessAllTask($limit = 10000)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		//1. Finish partial
		//2. Execute new tasks
		//3. Retry failed tasks
		//4. Run low priority tasks
		foreach (['P', 'N', 'R', 'L'] as $status)
		{
			if ($limit > 0)
			{
				$dbrTask = $connection->query($sql = '
					SELECT ID, RETRY_COUNT
					FROM b_controller_task
					WHERE STATUS = \'' . $status . '\'
					' . ($status === 'R' ? 'and DATE_EXECUTE < ' . $helper->addSecondsToDateTime('-RETRY_TIMEOUT') : '') . '
					ORDER BY ID ASC
				', $limit);
				while ($arTask = $dbrTask->fetch())
				{
					$new_status = CControllerTask::ProcessTask($arTask['ID']);
					while ($new_status === 'P')
					{
						$new_status = CControllerTask::ProcessTask($arTask['ID']);
					}

					if ($new_status === 'F' && $arTask['RETRY_COUNT'] > 0)
					{
						CControllerTask::PostponeTask($arTask['ID'], $arTask['RETRY_COUNT'] - 1);
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
