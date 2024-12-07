<?php

IncludeModuleLangFile(__FILE__);

class CControllerGroup
{
	public static function CheckDefaultUpdate()
	{
		$dbr_groups = CControllerGroup::GetList([], ['<UPDATE_PERIOD' => 0]);
		while ($ar_group = $dbr_groups->Fetch())
		{
			CControllerGroup::SetGroupSettings($ar_group['ID']);
		}

		return 'CControllerGroup::CheckDefaultUpdate();';
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		$arMsg = [];

		if ($ID > 0)
		{
			unset($arFields['ID']);
		}

		global $DB;
		if (($ID === false || is_set($arFields, 'NAME')) && $arFields['NAME'] == '')
		{
			$arMsg[] = ['id' => 'NAME', 'text' => GetMessage('CTRLR_GRP_ERR_NAME')];
		}

		if (isset($arFields['UPDATE_PERIOD']) && ($arFields['UPDATE_PERIOD'] < 0 || trim($arFields['UPDATE_PERIOD']) === ''))
		{
			$arFields['UPDATE_PERIOD'] = -1;
		}

		if (count($arMsg) > 0)
		{
			$e = new CAdminException($arMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		global $USER;
		if (!is_set($arFields, 'MODIFIED_BY') && is_object($USER))
		{
			$arFields['MODIFIED_BY'] = $USER->GetID();
		}
		if ($ID === false && !is_set($arFields, 'CREATED_BY') && is_object($USER))
		{
			$arFields['CREATED_BY'] = $USER->GetID();
		}
		if ($ID === false && !is_set($arFields, 'DATE_CREATE'))
		{
			$arFields['~DATE_CREATE'] = $DB->CurrentTimeFunction();
		}

		return true;
	}

	public static function Add($arFields)
	{
		global $DB, $USER_FIELD_MANAGER;

		if (!CControllerGroup::CheckFields($arFields))
		{
			return false;
		}

		if (!$USER_FIELD_MANAGER->CheckFields('CONTROLLER_GROUP', 0, $arFields))
		{
			return false;
		}

		unset($arFields['TIMESTAMP_X']);
		$arFields['~TIMESTAMP_X'] = $DB->CurrentTimeFunction();

		$ID = $DB->Add('b_controller_group', $arFields, ['DESCRIPTION', 'INSTALL_INFO', 'UNINSTALL_INFO', 'INSTALL_PHP', 'UNINSTALL_PHP']);

		$USER_FIELD_MANAGER->Update('CONTROLLER_GROUP', $ID, $arFields);

		if (isset($arFields['UPDATE_PERIOD']))
		{
			CControllerGroup::__UpdateAgentPeriod($ID, $arFields['UPDATE_PERIOD']);
		}

		if (isset($arFields['COUNTER_UPDATE_PERIOD']))
		{
			CControllerGroup::__CounterUpdateAgentPeriod($ID, $arFields['COUNTER_UPDATE_PERIOD']);
		}

		return $ID;

	}

	public static function Update($ID, $arFields)
	{
		global $DB, $USER_FIELD_MANAGER;

		if (!CControllerGroup::CheckFields($arFields, $ID))
		{
			return false;
		}

		if (!$USER_FIELD_MANAGER->CheckFields('CONTROLLER_GROUP', $ID, $arFields))
		{
			return false;
		}

		if (isset($arFields['UPDATE_PERIOD']) || isset($arFields['COUNTER_UPDATE_PERIOD']))
		{
			$dbr_group = CControllerGroup::GetByID($ID);
			$ar_group = $dbr_group->Fetch();
			if (isset($arFields['UPDATE_PERIOD']) && $ar_group['UPDATE_PERIOD'] != $arFields['UPDATE_PERIOD'])
			{
				CControllerGroup::__UpdateAgentPeriod($ID, $arFields['UPDATE_PERIOD']);
			}
			if (isset($arFields['COUNTER_UPDATE_PERIOD']) && $ar_group['COUNTER_UPDATE_PERIOD'] != $arFields['COUNTER_UPDATE_PERIOD'])
			{
				CControllerGroup::__CounterUpdateAgentPeriod($ID, $arFields['COUNTER_UPDATE_PERIOD']);
			}
		}

		unset($arFields['TIMESTAMP_X']);
		$arFields['~TIMESTAMP_X'] = $DB->CurrentTimeFunction();

		$arUpdateBinds = [];
		$strUpdate = $DB->PrepareUpdateBind('b_controller_group', $arFields, '', false, $arUpdateBinds);

		$strSql = 'UPDATE b_controller_group SET ' . $strUpdate . ' WHERE ID=' . intval($ID);

		$arBinds = [];
		foreach ($arUpdateBinds as $field_id)
		{
			$arBinds[$field_id] = $arFields[$field_id];
		}

		$res = $DB->QueryBind($strSql, $arBinds);
		if ($res)
		{
			$USER_FIELD_MANAGER->Update('CONTROLLER_GROUP', $ID, $arFields);
		}

		if (!$res)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public static function __UpdateAgentPeriod($group_id, $time)
	{
		$group_id = intval($group_id);
		$time = intval($time);

		CAgent::RemoveAgent('CControllerGroup::__UpdateSettingsAgent(' . $group_id . ');', 'controller');
		if ($time > 0)
		{
			CAgent::AddAgent('CControllerGroup::__UpdateSettingsAgent(' . $group_id . ');', 'controller', 'N', $time * 60);
		}
	}

	public static function __CounterUpdateAgentPeriod($group_id, $time)
	{
		$group_id = intval($group_id);
		$time = intval($time);

		CAgent::RemoveAgent('CControllerGroup::__UpdateCountersAgent(' . $group_id . ');', 'controller');
		if ($time > 0)
		{
			CAgent::AddAgent('CControllerGroup::__UpdateCountersAgent(' . $group_id . ');', 'controller', 'N', $time * 60);
		}
	}

	public static function Delete($ID)
	{
		global $DB, $USER_FIELD_MANAGER;
		$ID = intval($ID);
		if ($ID == 1)
		{
			$e = new CApplicationException(GetMessage('CTRL_GRP_DEL_ERR_DEF'));
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}
		$dbres = $DB->Query('SELECT 1 FROM b_controller_member WHERE CONTROLLER_GROUP_ID=' . $ID . ' limit 1');
		if ($dbres->Fetch())
		{
			$e = new CApplicationException(GetMessage('CTRLR_GRP_DEL_ERR'));
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		$USER_FIELD_MANAGER->Delete('CONTROLLER_GROUP', $ID);
		$DB->Query('DELETE FROM b_controller_counter_group WHERE CONTROLLER_GROUP_ID = ' . $ID);
		$DB->Query('DELETE FROM b_controller_group WHERE ID=' . $ID);
		return true;
	}

	public static function GetList($arOrder = [], $arFilter = [], $arSelect = [])
	{
		global $DB;

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity('CONTROLLER_GROUP', 'G.ID');
		$obUserFieldsSql->SetSelect($arSelect);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		static $arFields = [
			'ID' => [
				'FIELD_NAME' => 'G.ID',
				'FIELD_TYPE' => 'int',
			],
			'NAME' => [
				'FIELD_NAME' => 'G.NAME',
				'FIELD_TYPE' => 'string',
			],
			'TIMESTAMP_X' => [
				'FIELD_NAME' => 'G.TIMESTAMP_X',
				'FIELD_TYPE' => 'datetime',
			],
			'MODIFIED_BY' => [
				'FIELD_NAME' => 'G.MODIFIED_BY',
				'FIELD_TYPE' => 'int',
			],
			'UPDATE_PERIOD' => [
				'FIELD_NAME' => 'G.UPDATE_PERIOD',
				'FIELD_TYPE' => 'int',
			],
			'MODIFIED_BY_USER' => [
				'FIELD_TYPE' => 'string',
			],
			'DATE_CREATE' => [
				'FIELD_NAME' => 'G.DATE_CREATE',
				'FIELD_TYPE' => 'datetime',
			],
			'CREATED_BY' => [
				'FIELD_NAME' => 'G.CREATED_BY',
				'FIELD_TYPE' => 'int',
			],
			'CREATED_BY_USER' => [
				'FIELD_TYPE' => 'string',
			],
			'TRIAL_PERIOD' => [
				'FIELD_NAME' => 'G.TRIAL_PERIOD',
				'FIELD_TYPE' => 'int',
			],
			'COUNTER_UPDATE_PERIOD' => [
				'FIELD_NAME' => 'G.COUNTER_UPDATE_PERIOD',
				'FIELD_TYPE' => 'int',
			],
			'CHECK_COUNTER_FREE_SPACE' => [
				'FIELD_NAME' => 'G.CHECK_COUNTER_FREE_SPACE',
				'FIELD_TYPE' => 'string',
			],
			'CHECK_COUNTER_SITES' => [
				'FIELD_NAME' => 'G.CHECK_COUNTER_SITES',
				'FIELD_TYPE' => 'string',
			],
			'CHECK_COUNTER_USERS' => [
				'FIELD_NAME' => 'G.CHECK_COUNTER_USERS',
				'FIELD_TYPE' => 'string',
			],
			'CHECK_COUNTER_LAST_AUTH' => [
				'FIELD_NAME' => 'G.CHECK_COUNTER_LAST_AUTH',
				'FIELD_TYPE' => 'string',
			],
		];

		$arFields['MODIFIED_BY_USER']['FIELD_NAME'] = $DB->Concat('UM.LOGIN', 'UM.NAME', 'UM.LAST_NAME');
		$arFields['CREATED_BY_USER']['FIELD_NAME'] = $DB->Concat('UC.LOGIN', 'UC.NAME', 'UC.LAST_NAME');

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

		$strWhere = '1 = 1';
		$r = trim($obWhere->GetQuery($arFilterNew));
		if ($r !== '')
		{
			$strWhere .= ' AND (' . $r . ') ';
		}

		$r = trim($obUserFieldsSql->GetFilter());
		if ($r !== '')
		{
			$strWhere .= ' AND (' . $r . ') ';
		}

		$strSql = '
			SELECT ' . ($obUserFieldsSql->GetDistinct() ? 'DISTINCT' : '') . ' G.*
				,UC.LOGIN as CREATED_BY_LOGIN
				,UC.NAME as CREATED_BY_NAME
				,UC.LAST_NAME as CREATED_BY_LAST_NAME
				,UM.LOGIN as MODIFIED_BY_LOGIN
				,UM.NAME as MODIFIED_BY_NAME
				,UM.LAST_NAME as MODIFIED_BY_LAST_NAME
				,' . $DB->DateToCharFunction('G.TIMESTAMP_X') . ' as TIMESTAMP_X
				,' . $DB->DateToCharFunction('G.DATE_CREATE') . ' as DATE_CREATE
				' . $obUserFieldsSql->GetSelect() . '
			FROM b_controller_group G
				LEFT JOIN b_user UC ON UC.ID=G.CREATED_BY
				LEFT JOIN b_user UM ON UM.ID=G.MODIFIED_BY
				' . $obWhere->GetJoins() . '
				' . $obUserFieldsSql->GetJoin('G.ID') . '
			WHERE ' . $strWhere . '
			' . CControllerAgent::_OrderBy($arOrder, $arFields, $obUserFieldsSql) . '
		';

		$dbr = $DB->Query($strSql);
		$dbr->is_filtered = ($strWhere !== '');
		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CControllerGroup::GetList([], ['ID' => intval($ID)]);
	}

	public static function GetGroupSettings($group_id)
	{
		$dbr_group = CControllerGroup::GetByID($group_id);
		if ($ar_group = $dbr_group->Fetch())
		{
			$arSettings = unserialize($ar_group['INSTALL_INFO'], ['allowed_classes' => false]);
			$strCommand = CControllerGroupSettings::GeneratePHPInstall($arSettings);
			return $strCommand . $ar_group['INSTALL_PHP'];
		}

		return false;
	}

	public static function RunCommand($group_id, $php_script, $arParameters = [])
	{
		global $DB;
		$group_id = intval($group_id);

		if ($php_script == 'COUNTERS_UPDATE' || $php_script == 'SET_SETTINGS' || $php_script == 'UPDATE')
		{
			$task_id = $php_script;
			$php_script = '';
		}
		else
		{
			$task_id = 'REMOTE_COMMAND';
		}

		$arUpdateFields = [
			'~DATE_CREATE' => $DB->CurrentTimeFunction(),
			'INIT_EXECUTE' => ($php_script ?: false),
			'INIT_EXECUTE_PARAMS' => ($arParameters ? serialize($arParameters) : false),
		];
		$arUpdateBinds = [];
		$strUpdate = '
			UPDATE b_controller_task
			SET ' . $DB->PrepareUpdateBind('b_controller_task', $arUpdateFields, '', false, $arUpdateBinds) . '
			WHERE
				CONTROLLER_MEMBER_ID = #\'MID\'#
				AND TASK_ID = \'' . $task_id . '\'
				AND DATE_EXECUTE IS NULL
		';

		$arInsertFields = [
			'~DATE_CREATE' => $DB->CurrentTimeFunction(),
			'TASK_ID' => $task_id,
			'INIT_EXECUTE' => ($php_script ?: false),
			'INIT_EXECUTE_PARAMS' => ($arParameters ? serialize($arParameters) : false),
			'DATE_EXECUTE' => false,
		];

		$strSql = 'SELECT M.ID FROM b_controller_member M WHERE M.CONTROLLER_GROUP_ID = ' . $group_id . ' AND M.ACTIVE = \'Y\'';
		$rsMembers = $DB->Query($strSql);
		while ($arMember = $rsMembers->Fetch())
		{
			$arBinds = [];
			foreach ($arUpdateBinds as $field_id)
			{
				$arBinds[$field_id] = $arUpdateFields[$field_id];
			}
			$rsUpdate = $DB->QueryBind(str_replace("#'MID'#", $arMember['ID'], $strUpdate), $arBinds);

			if ($rsUpdate->AffectedRowsCount() <= 0)
			{
				$arInsertFields['CONTROLLER_MEMBER_ID'] = $arMember['ID'];
				$DB->Add('b_controller_task', $arInsertFields, ['INIT_EXECUTE', 'INIT_EXECUTE_PARAMS']);
			}
		}
	}

	public static function __UpdateCountersAgent($group_id)
	{
		CControllerGroup::UpdateCounters($group_id);
		return 'CControllerGroup::__UpdateCountersAgent(' . $group_id . ');';
	}

	public static function UpdateCounters($group_id)
	{
		CControllerGroup::RunCommand($group_id, 'COUNTERS_UPDATE');
	}

	public static function __UpdateSettingsAgent($group_id)
	{
		CControllerGroup::SetGroupSettings($group_id);
		return 'CControllerGroup::__UpdateSettingsAgent(' . $group_id . ');';
	}

	public static function SetGroupSettings($group_id)
	{
		CControllerGroup::RunCommand($group_id, 'SET_SETTINGS');
	}

	public static function SiteUpdate($group_id)
	{
		CControllerGroup::RunCommand($group_id, 'UPDATE');
	}
}

class CControllerGroupSettings
{
	public static function GetData()
	{
		$arModules = [
			'main' => [
				'name' => GetMessage('CTRLR_GRP_SET_MAIN_NAME'),
				'options' => [
					'component_cache_on' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_CACHE'), 'N', ['checkbox', 'Y']],
					'error_reporting' => [
						GetMessage('CTRLR_GRP_SET_MAIN_OPT_ERRREP'),
						85,
						['selectbox',
							[
								'85' => GetMessage('CTRLR_GRP_SET_MAIN_OPT_ERRREP_1'),
								'2039' => GetMessage('CTRLR_GRP_SET_MAIN_OPT_ERRREP_2'),
								'0' => GetMessage('CTRLR_GRP_SET_MAIN_OPT_ERRREP_3')
							]
						]
					],
					'all_bcc' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_EMAIL'), '', ['text', 30]],
					'disk_space' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_QUOTA'), '', ['text', 30]],

					'__registration' => GetMessage('CTRLR_GRP_SET_MAIN_OPT_REG'),
					'new_user_registration' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_CANREG'), 'N', ['checkbox', 'Y']],
					'store_password' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_STORE_AUTH'), 'Y', ['checkbox', 'Y']],
					'captcha_registration' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_CAPTCHA'), 'N', ['checkbox', 'Y']],
					'auth_comp2' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_C2_0'), 'N', ['checkbox', 'Y']],
					'auth_controller_prefix' => [GetMessage('CTRLR_GRP_SET_MAIN_PREFIX'), 'controller', ['text', '30']],
					'auth_controller_sso' => [GetMessage('CTRLR_GRP_SET_MAIN_AUTH_REM'), 'N', ['checkbox', 'Y']],

					'__updates' => GetMessage('CTRLR_GRP_SET_MAIN_OPT_UPD'),
					'update_site' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_UPD_SER'), '', ['text', 30]],
					'update_site_proxy_addr' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_UPD_PROXY'), '', ['text', 30]],
					'update_site_proxy_port' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_UPD_PROXY_PORT'), '', ['text', 30]],
					'update_site_proxy_user' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_UPD_PROXY_NAME'), '', ['text', 30]],
					'update_site_proxy_pass' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_UPD_PROXY_PASS'), '', ['text', 30]],
					'strong_update_check' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_UPD_STRONG'), 'Y', ['checkbox', 'Y']],
					'stable_versions_only' => [GetMessage('CTRLR_GRP_SET_MAIN_OPT_UPD_STABLE'), 'Y', ['checkbox', 'Y']],
				],
			],
			'fileman' => [
				'name' => GetMessage('CTRLR_GRP_SET_FILEMAN'),
				'options' => [
					'~allowed_components' => [GetMessage('CTRLR_GRP_SET_FILEMAN_OPT_AV_COMP'), '', ['textarea', 5, 30]],
				],
			],
			'advertising' => ['name' => GetMessage('CTRLR_GRP_SET_ADVERTISING')],
			'bitrix24' => ['name' => GetMessage('CTRLR_GRP_SET_BITRIX24')],
			'bizproc' => ['name' => GetMessage('CTRLR_GRP_SET_BIZPROC')],
			'bizprocdesigner' => ['name' => GetMessage('CTRLR_GRP_SET_BIZPROCDESIGNER')],
			'blog' => ['name' => GetMessage('CTRLR_GRP_SET_BLOG')],
			'calendar' => ['name' => GetMessage('CTRLR_GRP_SET_CALENDAR')],
			'catalog' => ['name' => GetMessage('CTRLR_GRP_SET_CATALOG')],
			'clouds' => ['name' => GetMessage('CTRLR_GRP_SET_CLOUDS')],
			'cluster' => ['name' => GetMessage('CTRLR_GRP_SET_CLUSTER')],
			'controller' => ['name' => GetMessage('CTRLR_GRP_SET_CONTROLLER')],
			'crm' => ['name' => GetMessage('CTRLR_GRP_SET_CRM')],
			'currency' => ['name' => GetMessage('CTRLR_GRP_SET_CURRENCY')],
			'dav' => ['name' => GetMessage('CTRLR_GRP_SET_DAV')],
			'extranet' => ['name' => GetMessage('CTRLR_GRP_SET_EXTRANET')],
			'form' => ['name' => GetMessage('CTRLR_GRP_SET_FORM')],
			'forum' => ['name' => GetMessage('CTRLR_GRP_SET_FORUM')],
			'iblock' => ['name' => GetMessage('CTRLR_GRP_SET_IBLOCK')],
			'idea' => ['name' => GetMessage('CTRLR_GRP_SET_IDEA')],
			'intranet' => ['name' => GetMessage('CTRLR_GRP_SET_INTRANET')],
			'ldap' => ['name' => GetMessage('CTRLR_GRP_SET_LDAP')],
			'learning' => ['name' => GetMessage('CTRLR_GRP_SET_LEARNING')],
			'lists' => ['name' => GetMessage('CTRLR_GRP_SET_LISTS')],
			'mail' => ['name' => GetMessage('CTRLR_GRP_SET_MAIL')],
			'meeting' => ['name' => GetMessage('CTRLR_GRP_SET_MEETING')],
			'perfmon' => ['name' => GetMessage('CTRLR_GRP_SET_PERFMON')],
			'photogallery' => ['name' => GetMessage('CTRLR_GRP_SET_PHOTOGALLERY')],
			'report' => ['name' => GetMessage('CTRLR_GRP_SET_REPORT')],
			'sale' => ['name' => GetMessage('CTRLR_GRP_SET_SALE')],
			'search' => ['name' => GetMessage('CTRLR_GRP_SET_SEARCH')],
			'security' => ['name' => GetMessage('CTRLR_GRP_SET_SECURITY')],
			'seo' => ['name' => GetMessage('CTRLR_GRP_SET_SEO')],
			'socialnetwork' => ['name' => GetMessage('CTRLR_GRP_SET_SOCIALNETWORK')],
			'socialservices' => ['name' => GetMessage('CTRLR_GRP_SET_SOCIALSERVICES')],
			'statistic' => ['name' => GetMessage('CTRLR_GRP_SET_STATISTIC')],
			'subscribe' => ['name' => GetMessage('CTRLR_GRP_SET_SUBSCRIBE')],
			'support' => ['name' => GetMessage('CTRLR_GRP_SET_SUPPORT')],
			'tasks' => ['name' => GetMessage('CTRLR_GRP_SET_TASKS')],
			'timeman' => ['name' => GetMessage('CTRLR_GRP_SET_TIMEMAN')],
			'translate' => ['name' => GetMessage('CTRLR_GRP_SET_TRANSLATE')],
			'vote' => ['name' => GetMessage('CTRLR_GRP_SET_VOTE')],
			'webdav' => ['name' => GetMessage('CTRLR_GRP_SET_WEBDAV')],
			'webservice' => ['name' => GetMessage('CTRLR_GRP_SET_WEBSERVICE')],
			'wiki' => ['name' => GetMessage('CTRLR_GRP_SET_WIKI')],
			'workflow' => ['name' => GetMessage('CTRLR_GRP_SET_WORKFLOW')],
			'xdimport' => ['name' => GetMessage('CTRLR_GRP_SET_XDIMPORT')],
			'xmpp' => ['name' => GetMessage('CTRLR_GRP_SET_XMPP')],
		];

		sortByColumn($arModules, 'name');

		return $arModules;
	}

	public static function GetModules()
	{
		$arInfo = CControllerGroupSettings::GetData();
		$arModules = [];
		foreach ($arInfo as $mname => $arProp)
		{
			$arModules[$mname] = $arProp['name'];
		}
		return $arModules;
	}

	public static function GenerateInput($id, $arInfo, $curVal = false, $context = 'default')
	{
		$res = '<input type="checkbox" id="' . htmlspecialcharsbx('ACT_' . $id) . '" ' . ($curVal === false ? '' : 'checked') . ' name="' . htmlspecialcharsbx('OPTIONS[' . $context . '][' . $id . ']') . '" value="Y" title="' . GetMessage('CTRLR_GRP_REASSIGN')
			. '" onclick="' . htmlspecialcharsbx('document.getElementById(\'' . CUtil::addslashes($id) . '\').disabled=!this.checked;if(this.checked)document.getElementById(\'' . CUtil::addslashes($id) . '\').focus();') . '">';
		if ($curVal === false)
		{
			$strDis = ' disabled ';
		}
		else
		{
			$strDis = '';
		}

		$arInput = $arInfo[2];
		if ($arInput[0] == 'checkbox')
		{
			$res .= '<select name="' . htmlspecialcharsbx('OPTIONS[' . $context . '][' . $id . ']') . '" id="' . htmlspecialcharsbx($id) . '"' . $strDis . '>'
				. '<option value="N">' . GetMessage('CTRLR_GRP_OPT_NO') . '</option>'
				. '<option value="Y"' . ($curVal !== false && $curVal == 'Y' ? ' selected' : '') . '>' . GetMessage('CTRLR_GRP_OPT_YES') . '</option>'
				. '</select>';
		}
		elseif ($arInput[0] == 'text')
		{
			$res .= '<input type="text" name="' . htmlspecialcharsbx('OPTIONS[' . $context . '][' . $id . ']') . '" ' . $strDis . 'id="' . htmlspecialcharsbx($id)
				. '" size="' . htmlspecialcharsbx($arInput[1]) . '" value="' . htmlspecialcharsbx($curVal === false ? $arInput[2] : $curVal) . '">';
		}
		elseif ($arInput[0] == 'selectbox')
		{
			$res .= '<select name="' . htmlspecialcharsbx('OPTIONS[' . $context . '][' . $id . ']') . '" ' . $strDis . 'id="' . htmlspecialcharsbx($id) . '">';
			foreach ($arInput[1] as $enum_id => $enum_value)
			{
				$res .= '<option value="' . htmlspecialcharsbx($enum_id) . '"'
					. ($curVal !== false && $curVal == $enum_id ? ' selected' : '')
					. '>' . htmlspecialcharsEx($enum_value) . '</option>';
			}
			$res .= '</select>';
		}
		elseif ($arInput[0] == 'textarea')
		{
			$res .= '<br><textarea rows="' . htmlspecialcharsbx($arInput[1]) . '" cols="' . htmlspecialcharsbx($arInput[2]) . '" name="' . htmlspecialcharsbx('OPTIONS[' . $context . '][' . $id . ']') . '" ' . $strDis . ' id="' . htmlspecialcharsbx($id) . '">' . htmlspecialcharsbx($curVal === false ? $arInput[3] : $curVal) . '</textarea>';
		}

		return $res;
	}

	/**
	 * @return array[]IControllerGroupOption
	 */
	public static function Get3rdPartyOptions()
	{
		$arResult = [];
		foreach (GetModuleEvents('controller', 'OnGetGroupSettings', true) as $arEvent)
		{
			$Object = ExecuteModuleEventEx($arEvent);
			if (is_object($Object))
			{
				$arResult[] = $Object;
			}
		}
		return $arResult;
	}

	public static function GeneratePHPInstall($arValues)
	{
		$str = '';
		$arDefValues = $arValues['default']['options'];
		$arInfo = CControllerGroupSettings::GetData();

		if (isset($arValues['default']['modules']))
		{
			$vArr = '';
			foreach ($arInfo as $module_id => $arProp)
			{
				if ($module_id == 'main')
				{
					continue;
				}

				if (in_array($module_id, $arValues['default']['modules']))
				{
					$vArr .= '"' . $module_id . '"=>"Y", ';
				}
				else
				{
					$vArr .= '"' . $module_id . '"=>"N", ';
				}
			}
			$str .= 'CControllerClient::SetModules(Array(' . $vArr . '));' . "\r\n";
		}
		else
		{
			$str .= 'CControllerClient::RestoreModules();' . "\r\n";
		}

		foreach ($arInfo as $mname => $arProp)
		{
			if (!is_array($arProp['options']) || !$arProp['options'])
			{
				continue;
			}
			$arOptions = $arProp['options'];
			foreach ($arOptions as $id => $arOptionParams)
			{
				if (isset($arDefValues[$mname][$id]))
				{
					$str .= 'CControllerClient::SetOptionString("' . EscapePHPString($mname) . '", "' . EscapePHPString($id) . '", "' . EscapePHPString($arDefValues[$mname][$id]) . '");' . "\r\n";
				}
				elseif (mb_substr($id, 0, 2) !== '__')
				{
					$str .= 'CControllerClient::RestoreOption("' . EscapePHPString($mname) . '", "' . EscapePHPString($id) . '");' . "\r\n";
				}
			}
		}

		$arSecurity = $arValues['default']['security'];
		if ($arSecurity['limit_admin'] === 'Y')
		{
			$str .= 'CControllerClient::SetOptionString("main", "~controller_limited_admin", "Y");' . "\r\n";
		}
		else
		{
			$str .= 'CControllerClient::SetOptionString("main", "~controller_limited_admin", "N");' . "\r\n";
		}

		$arGroups = [];
		$arUniqTasks = [];
		if (is_array($arSecurity['groups']))
		{
			foreach ($arSecurity['groups'] as $group_id => $arPermissions)
			{
				$arDefinedPermissions = [];
				$arUnDefinedPermissions = [];
				$bSubOrdGroups = false;
				foreach ($arInfo as $module_id => $arProp)
				{
					if (isset($arPermissions[$module_id]))
					{
						$arDefinedPermissions[$module_id] = $arPermissions[$module_id];

						$task_id = $arPermissions[$module_id];

						if (mb_strlen($task_id) > 1 && (!is_array($arUniqTasks[$module_id]) || !in_array($task_id, $arUniqTasks[$module_id])))
						{
							$arUniqTasks[$module_id][] = $task_id;
							$dbr_task = CTask::GetList([], ['NAME' => $task_id, 'MODULE_ID' => $module_id, 'BINDING' => 'module']);
							if ($ar_task = $dbr_task->Fetch())
							{
								if ($module_id == 'main' || $ar_task['SYS'] != 'Y')
								{
									$arOperations = CTask::GetOperations($ar_task['ID'], true);

									if ($ar_task['SYS'] != 'Y')
									{
										$str .= 'CControllerClient::SetTaskSecurity(' . CControllerGroupSettings::__PHPToString($task_id) . ', ' . CControllerGroupSettings::__PHPToString($module_id) . ', ' . CControllerGroupSettings::__PHPToString($arOperations) . ', ' . CControllerGroupSettings::__PHPToString($ar_task['LETTER']) . ');' . "\r\n";
									}

									if ($module_id == 'main' && in_array('edit_subordinate_users', $arOperations, true))
									{
										$bSubOrdGroups = true;
									}
								}
							}
						}
					}
					else
					{
						$arUnDefinedPermissions[] = $module_id;
					}
				}

				$str .= 'CControllerClient::RestoreGroupSecurity(' . CControllerGroupSettings::__PHPToString($group_id) . ', ' . CControllerGroupSettings::__PHPToString($arUnDefinedPermissions) . ');' . "\r\n";

				if ($bSubOrdGroups)
				{
					$arSGroupsTmp = preg_split("/[\r\n,;]+/", $arSecurity['subord_groups'][$group_id]);
					$arSGroups = [];
					foreach ($arSGroupsTmp as $sGroupTmp)
					{
						$sGroupTmp = trim($sGroupTmp);
						if ($sGroupTmp !== '')
						{
							$arSGroups[] = $sGroupTmp;
						}
					}

					$str .= 'CControllerClient::SetGroupSecurity(' . CControllerGroupSettings::__PHPToString($group_id) . ', ' . CControllerGroupSettings::__PHPToString($arDefinedPermissions) . ', ' . CControllerGroupSettings::__PHPToString($arSGroups) . ');' . "\r\n";
				}
				else
				{
					$str .= 'CControllerClient::SetGroupSecurity(' . CControllerGroupSettings::__PHPToString($group_id) . ', ' . CControllerGroupSettings::__PHPToString($arDefinedPermissions) . ');' . "\r\n";
				}

				$arGroups[] = $group_id;
			}
		}

		$str .= 'CControllerClient::RestoreSecurity(' . CControllerGroupSettings::__PHPToString($arGroups) . ');' . "\r\n";

		$arThirdSettings = CControllerGroupSettings::Get3rdPartyOptions();
		/** @var IControllerGroupOption $obOption */
		foreach ($arThirdSettings as $obOption)
		{
			$str .= $obOption->GetOptionPHPCode($arValues);
		}

		return $str;
	}

	protected static function __PHPToString($arData)
	{
		if (is_array($arData))
		{
			if ($arData == array_values($arData))
			{
				foreach ($arData as $key => $value)
				{
					$arData[$key] = CControllerGroupSettings::__PHPToString($value);
				}

				$res = 'Array(' . implode(', ', $arData) . ')';
			}
			else
			{
				$res = 'Array(';
				foreach ($arData as $key => $value)
				{
					$res .= '"' . EscapePHPString($key) . '" => ';
					$res .= CControllerGroupSettings::__PHPToString($value) . ', ';
				}
				$res .= ')';
			}
			return $res;
		}
		else
		{
			return '"' . EscapePHPString($arData) . '"';
		}
	}

	public function SetGroupSettings()
	{
	}
}

class IControllerGroupOption
{
	public $id = 'UNDEFINED';

	public function GetName()
	{
		return GetMessage('CTRLR_GRP_SETTINGS') . ' ' . $this->id;
	}

	public function GetIcon()
	{
		return 'controller_group_edit';
	}

	public function GetTitle()
	{
		return GetMessage('CTRLR_GRP_SETTINGS_TITLE');
	}

	public function GetOptionArray()
	{
		return [];
	}

	public function GetOptionPHPCode($arAllValues)
	{
		$arValues = $arAllValues[$this->id];
		$arOptions = $this->GetOptionArray();
		$str = '';
		foreach ($arOptions as $id => $_)
		{
			if (isset($arValues[$id]))
			{
				$str .= 'CControllerClient::SetOptionString("' . EscapePHPString($this->id) . '", "' . EscapePHPString($id) . '", "' . EscapePHPString($arValues[$id]) . '");' . "\r\n";
			}
			elseif (mb_substr($id, 0, 2) !== '__')
			{
				$str .= 'CControllerClient::RestoreOption("' . EscapePHPString($this->id) . '", "' . EscapePHPString($id) . '");' . "\r\n";
			}
		}
		return $str;
	}
}
