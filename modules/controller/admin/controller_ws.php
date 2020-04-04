<?
define("NOT_CHECK_PERMISSIONS", true);
define("NO_KEEP_STATISTIC", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */
/** @var CUser $USER */

CModule::IncludeModule("controller");

$oRequest = new CControllerServerRequestFrom();
$oResponse = new CControllerServerResponseTo($oRequest);
$skip_handler = false;

function __try_run()
{
	global $skip_handler, $oResponse;
	if ($skip_handler)
		return "";

	$res = ob_get_contents();

	$oResponse->status = "500 Execution Error";
	$oResponse->text = $res;
	return $oResponse->GetResponseBody(true);
}

ob_start("__try_run");

if ($oRequest->operation != 'join' && !$oRequest->Check())
{
	$oResponse->status = "403 Access Denied";
	$oResponse->text = "Access Denied";
}
else
{
	switch ($oRequest->operation)
	{
	case 'remote_auth':
		$url = $oRequest->arParameters['site'];
		$url = CControllerMember::_GoodURL($url);
		$dbr_mem = CControllerMember::GetList(array(), array(
			"=URL" => $url,
			"=DISCONNECTED" => "N",
			"=ACTIVE" => "Y",
		));
		$ar_mem = $dbr_mem->Fetch();

		if (!$ar_mem)
		{
			$oResponse->status = "472 Bad site.";
			$oResponse->text = "Invalid site ID";
			break;
		}

		$res = CControllerMember::CheckUserAuth($ar_mem["ID"], $oRequest->arParameters['login'], $oRequest->arParameters['password']);
		if (is_array($res))
		{
			$oResponse->arParameters = $res;
			$oResponse->status = "200 OK";
			if (\Bitrix\Controller\AuthLogTable::isEnabled())
			{
				$dbr = CControllerMember::GetByGuid($oRequest->member_id);
				$fromMember = $dbr->Fetch();
				\Bitrix\Controller\AuthLogTable::logSiteToSiteAuth(
					$ar_mem["ID"],
					$fromMember["ID"],
					true,
					'CONTROLLER_WS',
					$res['USER_INFO']['NAME'].' '.$res['USER_INFO']['LAST_NAME'].' ('.$res['USER_INFO']['LOGIN'].')'
				);
			}
		}
		else
		{
			$oResponse->status = "473 Bad password.";
			$e = $APPLICATION->GetException();
			$oResponse->text = $e->GetString();
		}

		break;
	case 'check_auth':
		$dbr = CControllerMember::GetByGuid($oRequest->member_id);
		$arControllerMember = $dbr->Fetch();

		$arControllerLog = array(
			'NAME' => 'AUTH',
			'CONTROLLER_MEMBER_ID' => $arControllerMember["ID"],
			'STATUS' => 'Y',
		);

		$err = '';
		$arParams = array(
			"LOGIN" => &$oRequest->arParameters['login'],
			"PASSWORD" => &$oRequest->arParameters['password'],
			"PASSWORD_ORIGINAL" => "Y",
		);

		foreach (GetModuleEvents("main", "OnBeforeUserLogin", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arParams)) === false)
			{
				if ($e = $APPLICATION->GetException())
					$err = $e->GetString();
				else
					$err = 'Unknown event error';
				break;
			}
		}

		$user_id = 0;
		if (!$err)
		{
			//external authentication
			foreach (GetModuleEvents("main", "OnUserLoginExternal", true) as $arEvent)
			{
				$user_id = ExecuteModuleEventEx($arEvent, array(&$arParams));
				if ($user_id > 0)
				{
					break;
				}
			}
		}

		if ($user_id > 0)
			$dbUser = CUser::GetByID($user_id);
		else
			$dbUser = CUser::GetByLogin($oRequest->arParameters['login']);

		if (!($arUser = $dbUser->Fetch()))
		{
			$oResponse->status = "444 User is not found.";
			$oResponse->text = "User is not found.";
			$arControllerLog['STATUS'] = 'N';
			$arControllerLog['DESCRIPTION'] = $oResponse->text;
			$a = CControllerLog::Add($arControllerLog);
		}
		else
		{
			if (strlen($arUser["PASSWORD"]) > 32)
			{
				$salt = substr($arUser["PASSWORD"], 0, strlen($arUser["PASSWORD"]) - 32);
				$db_password = substr($arUser["PASSWORD"], -32);
			}
			else
			{
				$salt = "";
				$db_password = $arUser["PASSWORD"];
			}

			$altPassword = null;
			if ($arParams['OTP'])
			{
				$altPassword = substr($oRequest->arParameters['password'], 0, -6);
			}

			if ($err)
			{
				$oResponse->status = "445 Event Error.";
				$oResponse->text = $err;
				$arControllerLog['STATUS'] = 'N';
				$arControllerLog['DESCRIPTION'] = $oResponse->text;
				$a = CControllerLog::Add($arControllerLog);
			}
			elseif (
				$arUser['ACTIVE'] == 'Y'
				&& (
					$user_id > 0 //External auth
					|| md5($db_password.'MySalt') == md5(md5($salt.$oRequest->arParameters['password']).'MySalt')
					|| (
						$altPassword
						&& md5($db_password.'MySalt') == md5(md5($salt.$altPassword).'MySalt')
					)
				)
			)
			{
				$arSaveUser = CControllerClient::PrepareUserInfo($arUser);

				$arSaveUser["GROUP_ID"] = Array();

				$bCanAuthorize = $USER->CanDoOperation("controller_member_auth", $arUser['ID']);
				$arUserGroups = CUser::GetUserGroup($arUser['ID']);

				$arParams['USER_ID'] = $arUser['ID'];
				if (
					CModule::IncludeModule('security')
					&& !\Bitrix\Security\Mfa\Otp::verifyUser($arParams)
				)
				{
					$oResponse->status = "443 Bad password.";
					$oResponse->text = GetMessage("CTRLR_WS_ERR_BAD_PASSW");
					break;
				}
				elseif ($bCanAuthorize)
				{
					$arSaveUser['CONTROLLER_ADMIN'] = 'Y';
					$arSaveUser["GROUP_ID"][] = "administrators";
				}
				elseif (COption::GetOptionString("controller", "auth_loc_enabled", "N") != "Y")
				{
					$oResponse->status = "423 Remote Authorization Disabled.";
					$oResponse->text = "Remote authorization disabled on controller.";
					break;
				}

				$arLocGroups = \Bitrix\Controller\GroupMapTable::getMapping("CONTROLLER_GROUP_ID", "REMOTE_GROUP_CODE");
				foreach ($arLocGroups as $arTGroup)
				{
					foreach ($arUserGroups as $group_id)
					{
						if ($arTGroup["FROM"] == $group_id)
							$arSaveUser["GROUP_ID"][] = $arTGroup["TO"];
					}
				}

				foreach (GetModuleEvents("controller", "OnBeforeSendCheckAuth", true) as $arEvent)
				{
					ExecuteModuleEventEx($arEvent, array($arControllerMember, &$arSaveUser));
				}

				$oResponse->status = "200 OK";
				$oResponse->arParameters['USER_INFO'] = $arSaveUser;

				$arControllerLog['DESCRIPTION'] = $arSaveUser['NAME'].' '.$arSaveUser['LAST_NAME'].' ('.$arSaveUser['LOGIN'].')';
				$a = CControllerLog::Add($arControllerLog);
				if (\Bitrix\Controller\AuthLogTable::isEnabled())
				{
					\Bitrix\Controller\AuthLogTable::logControllerToSiteAuth(
						$arControllerMember["ID"],
						$arUser['ID'],
						true,
						'CONTROLLER_WS',
						$arSaveUser['NAME'].' '.$arSaveUser['LAST_NAME'].' ('.$arSaveUser['LOGIN'].')'
					);
				}
			}
			else
			{
				$oResponse->status = "443 Bad password.";
				$oResponse->text = GetMessage("CTRLR_WS_ERR_BAD_PASSW");
				$arControllerLog['STATUS'] = 'N';
				$arControllerLog['DESCRIPTION'] = $oResponse->text;
				$a = CControllerLog::Add($arControllerLog);
			}
		}

		break;

	case 'join';
		// check rights for add
		if ($USER->Login($oRequest->arParameters['admin_login'], $oRequest->arParameters['admin_password']) !== true)
		{
			$oResponse->status = "413 Bad login";
			$oResponse->text = GetMessage("CTRLR_WS_ERR_BAD_LEVEL");
			break;
		}

		if (!$USER->CanDoOperation("controller_member_add"))
		{
			$oResponse->status = "413 Bad admin";
			$oResponse->text = GetMessage("CTRLR_WS_ERR_BAD_LEVEL");
			break;
		}

		$oResponse->secret_id = $oRequest->arParameters['member_secret_id'];

		// check if that site is agree?
		//if(!($res = CControllerMember::RegisterMemberByTicket($oRequest->member_id, $oRequest->arParameters['member_secret_id'], $oRequest->arParameters['ticket_id'], $oRequest->arParameters['url'], $oRequest->session_id)))
		$ar_member = Array(
			"MEMBER_ID" => $oRequest->member_id,
			"SECRET_ID" => $oRequest->arParameters['member_secret_id'],
			"NAME" => (strlen($oRequest->arParameters['name']) > 0? $oRequest->arParameters['name']: $oRequest->arParameters['url']),
			"URL" => $oRequest->arParameters['url'],
			"EMAIL" => $oRequest->arParameters['email'],
			"CONTACT_PERSON" => $oRequest->arParameters['contact_person'],
			"CONTROLLER_GROUP_ID" => (
			$oRequest->arParameters['group_id']
				? $oRequest->arParameters['group_id']
				: COption::GetOptionInt("controller", "default_group", 1)
			),
			"SHARED_KERNEL" => ($oRequest->arParameters['shared_kernel'] == "Y"? "Y": "N"),
		);

		$dbr_mem = CControllerMember::GetList(Array(), Array("URL" => CControllerMember::_GoodURL($oRequest->arParameters['url']), "DISCONNECTED" => "I"));
		if (($ar_mem = $dbr_mem->Fetch()) && CControllerMember::_GoodURL($ar_mem["URL"]) == CControllerMember::_GoodURL($oRequest->arParameters['url']))
			$ar_member["ID"] = $ar_mem["ID"];


		if ($ID = CControllerMember::RegisterMemberByTicket($ar_member, $oRequest->arParameters['ticket_id'], $oRequest->session_id))
		{
			$oResponse->status = "200 OK";
			$oResponse->arParameters['ID'] = $ID;
		}
		else
		{
			$oResponse->status = "453 RegisterMemberByTicket error";
			$e = $APPLICATION->GetException();
			$oResponse->text = $e->GetString();
		}

		break;
		// all ok? then we need update settings
	case 'init_group_update':
		$dbr = CControllerMember::GetByGuid($oRequest->member_id);
		if ($ar = $dbr->Fetch())
		{
			if (CControllerMember::SetGroupSettings($ar["ID"]) !== false)
				$oResponse->status = "200 OK";
			else
			{
				$oResponse->status = "510 Set group settings error";
				$e = $APPLICATION->GetException();
				$oResponse->text = $e->GetString();
			}
		}
		else
		{
			$oResponse->status = "404 Member is not found";
			$oResponse->text = GetMessage("CTRLR_WS_ERR_MEMB_NFOUND");
		}
		break;

	case 'remove':
		$USER->Login($oRequest->arParameters['admin_login'], $oRequest->arParameters['admin_password']);
		if (!$USER->CanDoOperation("controller_member_disconnect"))
		{
			$oResponse->status = "416 Bad admin";
			$oResponse->text = GetMessage("CTRLR_WS_ERR_MEMB_DISCN");
			break;
		}

		$dbr = CControllerMember::GetByGuid($oRequest->member_id);
		if (!($ar = $dbr->Fetch()))
		{
			$oResponse->status = "484";
			$oResponse->text = GetMessage("CTRLR_WS_ERR_MEMB_NFOUND");
			break;
		}

		if (CControllerMember::RemoveGroupSettings($ar["ID"]))
		{
			if (CControllerMember::UnRegister($ar["ID"]))
			{
				$oResponse->Sign(); // sign the response before deleting
				//CControllerMember::Delete($ar["ID"]);
				$oResponse->status = "200 OK";
			}
			else
			{
				$oResponse->status = "576 Unregister error";
				$e = $APPLICATION->GetException();
				$oResponse->text = $e->GetString();
			}
		}
		else
		{
			$oResponse->status = "545 Remove group settings error";
			$e = $APPLICATION->GetException();
			$oResponse->text = $e->GetString();
		}
		break;
	case 'query':
		$arCommand = CControllerMember::_CheckCommandId($oRequest->member_id, $oRequest->arParameters['command_id']);
		set_time_limit(1200);
		if ($arCommand !== false)
		{
			if ($oRequest->arParameters['sendfile'] == 'Y' && strlen($arCommand['ADD_PARAMS']) > 3)
			{
				$arParams = unserialize($arCommand['ADD_PARAMS']);
				if (is_array($arParams) && array_key_exists('FILE', $arParams))
				{
					$oResponse->status = '200 OK';
					$oResponse->arParameters['command'] = $arCommand['COMMAND'];
					$oResponse->arParameters['path_to'] = $arParams['PATH_TO'];

					if (file_exists($_SERVER['DOCUMENT_ROOT'].$arParams['FILE']))
						$oResponse->arParameters['file'] = file_get_contents($_SERVER['DOCUMENT_ROOT'].$arParams['FILE']);
					elseif (file_exists($arParams['FILE']))
						$oResponse->arParameters['file'] = file_get_contents($arParams['FILE']);
				}
				else
				{
					$oResponse->status = '555 File not found';
					$oResponse->text = GetMessage('CTRLR_WS_ERR_FILE_NOT_FOUND');
				}
			}
			elseif (strlen($arCommand['COMMAND']) > 0)
			{
				$oResponse->status = '200 OK';
				$oResponse->arParameters['query'] = $arCommand['COMMAND'];
			}
			else
			{
				$oResponse->status = "404 Command not found";
				$oResponse->text = GetMessage("CTRLR_WS_ERR_BAD_COMMAND");
			}
		}
		else
		{
			$oResponse->status = "404 Command not found";
			$oResponse->text = GetMessage("CTRLR_WS_ERR_BAD_COMMAND");
		}
		break;
	case 'log':

		$dbr = CControllerMember::GetByGuid($oRequest->member_id);
		$ar = $dbr->Fetch();
		if (!$ar)
		{
			$oResponse->status = "484";
			$oResponse->text = GetMessage("CTRLR_WS_ERR_MEMB_NFOUND");
			break;
		}

		$a = CControllerLog::Add(array(
			"CONTROLLER_MEMBER_ID" => $ar["ID"],
			"NAME" => $oRequest->arParameters['NAME'],
			"DESCRIPTION" => $oRequest->arParameters['DESCRIPTION'],
		));

		if ($a > 0)
		{
			$oResponse->status = "200 OK";
		}
		else
		{
			$oResponse->status = "500 Execution error";
			$e = $APPLICATION->GetException();
			$oResponse->text = $e->GetString();
		}
		break;

	case 'update_counters':
		$dbr = CControllerMember::GetByGuid($oRequest->member_id);
		if (!($ar = $dbr->Fetch()))
		{
			$oResponse->status = "484";
			$oResponse->text = GetMessage("CTRLR_WS_ERR_MEMB_NFOUND");
			break;
		}

		if (is_array(CControllerMember::UpdateCounters($ar["ID"])))
		{
			$oResponse->status = "200 OK";
		}
		else
		{
			$oResponse->status = "500 Execution error";
			$e = $APPLICATION->GetException();
			$oResponse->text = $e->GetString();
		}

		break;

	case 'execute_event':
		$rsClient = CControllerMember::GetByGuid($oRequest->member_id);
		$arClient = $rsClient->Fetch();
		if (!$arClient)
		{
			$oResponse->status = "484";
			$oResponse->text = GetMessage("CTRLR_WS_ERR_MEMB_NFOUND");
			break;
		}

		$params = $oRequest->arParameters['parameters'];
		array_unshift($params, $arClient);

		$result = false;
		foreach (GetModuleEvents("controller", $oRequest->arParameters['event_name'], true) as $arEvent)
		{
			$result = ExecuteModuleEventEx($arEvent, $params);
		}

		if ($result !== false)
		{
			$oResponse->arParameters['result'] = $result;
			$oResponse->status = "200 OK";
		}
		else
		{
			$oResponse->status = "500 Execution error";
			$e = $APPLICATION->GetException();
			if (is_object($e))
				$oResponse->text = $e->GetString();
		}

		break;
	default:
		$oResponse->status = "400 Bad operation";
		$oResponse->text = GetMessage("CTRLR_WS_ERR_BAD_OPERID").$oRequest->operation;

		break;
	}
}

$skip_handler = true;

$oResponse->text .= ob_get_contents();
ob_end_clean();

if ($oRequest->Internal())
{
	$oResponse->Send();
}
else
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	if ($oResponse->OK())
	{
		echo $oResponse->text;
	}
	else
	{
		ShowError(GetMessage("CTRLR_WS_ERR_RUN").$oResponse->text.'. '.GetMessage("CTRLR_WS_ERR_RUN_TRY"));
		if (strlen($_SERVER['HTTP_REFERER']) > 0)
			echo '<br>'.'<a href="'.htmlspecialcharsbx(CHTTP::urnEncode($_SERVER['HTTP_REFERER'])).'">'.GetMessage("CTRLR_WS_ERR_RUN_BACK").'</a>';
	}
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
}
?>
