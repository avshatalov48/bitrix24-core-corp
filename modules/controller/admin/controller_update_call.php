<?php
//**********************************************************************/
//**    DO NOT MODIFY THIS FILE                                       **/
//**    MODIFICATION OF THIS FILE WILL ENTAIL SITE FAILURE            **/
//**********************************************************************/
define('US_CALL_TYPE', 'KERNEL');
define('US_BASE_MODULE', 'controller');

$US_LICENSE_KEY = '';
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/license_key.php'))
{
	include $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/license_key.php';
}
if ($US_LICENSE_KEY === '' || mb_strtoupper($US_LICENSE_KEY) === 'DEMO')
{
	define('US_LICENSE_KEY', 'DEMO');
}
else
{
	define('US_LICENSE_KEY', $US_LICENSE_KEY);
}


if (!defined('UPD_INTERNAL_CALL') || constant('UPD_INTERNAL_CALL') !== 'Y')
{
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
	define('US_SHARED_KERNEL_PATH', COption::GetOptionString('controller', 'shared_kernel_path', '/bitrix/clients'));
	define('US_SAVE_UPDATERS_DIR', US_SHARED_KERNEL_PATH . '/updaters');
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/update_client.php';
}
else
{
	define('US_SHARED_KERNEL_PATH', COption::GetOptionString('controller', 'shared_kernel_path', '/bitrix/clients'));
	define('US_SAVE_UPDATERS_DIR', US_SHARED_KERNEL_PATH . '/updaters');
}
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */
/** @var CUser $USER */
CModule::IncludeModule('controller');

@set_time_limit(0);
ini_set('track_errors', '1');
ignore_user_abort(true);

IncludeModuleLangFile(__FILE__);

if (!$USER->CanDoOperation('controller_member_updates_run')  || !check_bitrix_sessid())
{
	$GLOBALS['APPLICATION']->AuthForm(GetMessage('ACCESS_DENIED'));
	die();
}

$errorMessage = '';

$stableVersionsOnly = COption::GetOptionString('controller', 'stable_versions_only', 'Y');

$queryType = $_REQUEST['query_type'];
if (!in_array($queryType, ['M', 'L', 'H']))
{
	$queryType = 'M';
}

$arRequestedModules = [];
if (array_key_exists('requested_modules', $_REQUEST))
{
	$arRequestedModulesTmp = explode(',', $_REQUEST['requested_modules']);
	for ($i = 0, $cnt = count($arRequestedModulesTmp); $i < $cnt; $i++)
	{
		if (!in_array($arRequestedModulesTmp[$i], $arRequestedModules, true))
		{
			$arRequestedModules[] = $arRequestedModulesTmp[$i];
		}
	}
}

$arRequestedLangs = [];
if (array_key_exists('requested_langs', $_REQUEST))
{
	$arRequestedLangsTmp = explode(',', $_REQUEST['requested_langs']);
	for ($i = 0, $cnt = count($arRequestedLangsTmp); $i < $cnt; $i++)
	{
		if (!in_array($arRequestedLangsTmp[$i], $arRequestedLangs, true))
		{
			$arRequestedLangs[] = $arRequestedLangsTmp[$i];
		}
	}
}

$arRequestedHelps = [];
if (array_key_exists('requested_helps', $_REQUEST))
{
	$arRequestedHelpsTmp = explode(',', $_REQUEST['requested_helps']);
	for ($i = 0, $cnt = count($arRequestedHelpsTmp); $i < $cnt; $i++)
	{
		if (!in_array($arRequestedHelpsTmp[$i], $arRequestedHelps, true))
		{
			$arRequestedHelps[] = $arRequestedHelpsTmp[$i];
		}
	}
}

COption::SetOptionString(US_BASE_MODULE, 'update_system_update', date(CDatabase::DateFormatToPHP(CSite::GetDateFormat('FULL')), time()));

/************************************/
if ($queryType === 'M')
{
	if (!CUpdateClient::GetNextStepUpdates($errorMessage, LANG, $stableVersionsOnly, $arRequestedModules))
	{
		$errorMessage .= '[CL01] ' . GetMessage('SUPC_ME_LOAD') . '. ';
		CUpdateClient::AddMessage2Log(GetMessage('SUPC_ME_LOAD'), 'CL01');
	}

	$temporaryUpdatesDir = '';
	if ($errorMessage === '')
	{
		if (!CUpdateClient::UnGzipArchive($temporaryUpdatesDir, $errorMessage, true))
		{
			$errorMessage .= '[CL02] ' . GetMessage('SUPC_ME_PACK') . '. ';
			CUpdateClient::AddMessage2Log(GetMessage('SUPC_ME_PACK'), 'CL02');
		}
	}

	if ($errorMessage === '')
	{
		if (!CUpdateClient::CheckUpdatability($temporaryUpdatesDir, $errorMessage))
		{
			$errorMessage .= '[CL03] ' . GetMessage('SUPC_ME_CHECK') . '. ';
			CUpdateClient::AddMessage2Log(GetMessage('SUPC_ME_CHECK'), 'CL03');
		}
	}

	$arStepUpdateInfo = [];
	if ($errorMessage === '')
	{
		$arStepUpdateInfo = CUpdateClient::GetStepUpdateInfo($temporaryUpdatesDir, $errorMessage);
	}

	if ($errorMessage === '')
	{
		if (isset($arStepUpdateInfo['DATA']['#']['ERROR']))
		{
			for ($i = 0, $cnt = count($arStepUpdateInfo['DATA']['#']['ERROR']); $i < $cnt; $i++)
			{
				$errorMessage .= '[' . $arStepUpdateInfo['DATA']['#']['ERROR'][$i]['@']['TYPE'] . '] ' . $arStepUpdateInfo['DATA']['#']['ERROR'][$i]['#'];
			}
		}
	}

	$arItemsUpdated = [];
	if ($errorMessage === '')
	{
		if (isset($arStepUpdateInfo['DATA']['#']['ITEM']))
		{
			for ($i = 0, $cnt = count($arStepUpdateInfo['DATA']['#']['ITEM']); $i < $cnt; $i++)
			{
				$arItemsUpdated[$arStepUpdateInfo['DATA']['#']['ITEM'][$i]['@']['NAME']] = $arStepUpdateInfo['DATA']['#']['ITEM'][$i]['@']['VALUE'];
			}
		}
	}

	if ($errorMessage === '')
	{
		if (isset($arStepUpdateInfo['DATA']['#']['NOUPDATES']))
		{
			CUpdateClient::ClearUpdateFolder($_SERVER['DOCUMENT_ROOT'] . '/bitrix/updates/' . $temporaryUpdatesDir);
			CUpdateClient::AddMessage2Log('Finish - NOUPDATES', 'STEP');
			echo 'FIN';

			if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/classes/general/update_db_updater.php'))
			{
				copy($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/controller/classes/general/update_db_updater.php', $_SERVER['DOCUMENT_ROOT'] . US_SHARED_KERNEL_PATH . '/modules/main/classes/general/update_db_updater.php');
			}

			CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'Y', 'DESCRIPTION' => GetMessage('SUPC_ME_C_UPDATED')]);
		}
		else
		{
			if ($errorMessage === '')
			{
				if (!CUpdateClient::UpdateStepModules($temporaryUpdatesDir, $errorMessage, true))
				{
					$errorMessage .= '[CL04] ' . GetMessage('SUPC_ME_UPDATE') . '. ';
					CUpdateClient::AddMessage2Log(GetMessage('SUPC_ME_UPDATE'), 'CL04');
				}
			}

			if ($errorMessage !== '')
			{
				CUpdateClient::AddMessage2Log('Error: ' . $errorMessage, 'UPD_ERROR');
				echo 'ERR' . $errorMessage;
				CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'N', 'DESCRIPTION' => $errorMessage]);
			}
			else
			{
				CUpdateControllerSupport::CollectVersionsFile();

				echo 'STP';
				echo count($arItemsUpdated) . '|';
				$bFirst = true;
				$strTmp = '';
				foreach ($arItemsUpdated as $key => $value)
				{
					CUpdateClient::AddMessage2Log('Updated: ' . $key . (($value <> '') ? '(' . $value . ')' : ''), 'UPD_SUCCESS');
					$strTmp .= ($bFirst ? '' : ', ') . $key . (($value <> '') ? ' (' . $value . ')' : '');
					echo ($bFirst ? '' : ', ') . $key . (($value <> '') ? ' (' . $value . ')' : '');
					$bFirst = false;
				}
				CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'Y', 'DESCRIPTION' => $strTmp]);
			}
		}
	}
	else
	{
		CUpdateClient::AddMessage2Log('Error: ' . $errorMessage, 'UPD_ERROR');
		echo 'ERR' . $errorMessage;
		CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'N', 'DESCRIPTION' => $errorMessage]);
	}
}
elseif ($queryType === 'L')
{
	if (!CUpdateClient::GetNextStepLangUpdates($errorMessage, LANG, $arRequestedLangs))
	{
		$errorMessage .= '[CL01] ' . GetMessage('SUPC_ME_LOAD') . '. ';
		CUpdateClient::AddMessage2Log(GetMessage('SUPC_ME_LOAD'), 'CL01');
	}

	$temporaryUpdatesDir = '';
	if ($errorMessage === '')
	{
		if (!CUpdateClient::UnGzipArchive($temporaryUpdatesDir, $errorMessage, true))
		{
			$errorMessage .= '[CL02] ' . GetMessage('SUPC_ME_PACK') . '. ';
			CUpdateClient::AddMessage2Log(GetMessage('SUPC_ME_PACK'), 'CL02');
		}
	}

	$arStepUpdateInfo = [];
	if ($errorMessage === '')
	{
		$arStepUpdateInfo = CUpdateClient::GetStepUpdateInfo($temporaryUpdatesDir, $errorMessage);
	}

	if ($errorMessage === '')
	{
		if (isset($arStepUpdateInfo['DATA']['#']['ERROR']))
		{
			for ($i = 0, $cnt = count($arStepUpdateInfo['DATA']['#']['ERROR']); $i < $cnt; $i++)
			{
				$errorMessage .= '[' . $arStepUpdateInfo['DATA']['#']['ERROR'][$i]['@']['TYPE'] . '] ' . $arStepUpdateInfo['DATA']['#']['ERROR'][$i]['#'];
			}
		}
	}

	$arItemsUpdated = [];
	if ($errorMessage === '')
	{
		if (isset($arStepUpdateInfo['DATA']['#']['ITEM']))
		{
			for ($i = 0, $cnt = count($arStepUpdateInfo['DATA']['#']['ITEM']); $i < $cnt; $i++)
			{
				$arItemsUpdated[$arStepUpdateInfo['DATA']['#']['ITEM'][$i]['@']['NAME']] = $arStepUpdateInfo['DATA']['#']['ITEM'][$i]['@']['VALUE'];
			}
		}
	}

	if ($errorMessage === '')
	{
		if (isset($arStepUpdateInfo['DATA']['#']['NOUPDATES']))
		{
			CUpdateClient::ClearUpdateFolder($_SERVER['DOCUMENT_ROOT'] . '/bitrix/updates/' . $temporaryUpdatesDir);
			CUpdateClient::AddMessage2Log('Finish - NOUPDATES', 'STEP');
			echo 'FIN';
			CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'Y', 'DESCRIPTION' => GetMessage('SUPC_LE_C_UPDATED')]);
		}
		else
		{
			if ($errorMessage === '')
			{
				if (!CUpdateClient::UpdateStepLangs($temporaryUpdatesDir, $errorMessage))
				{
					$errorMessage .= '[CL04] ' . GetMessage('SUPC_LE_UPD') . '. ';
					CUpdateClient::AddMessage2Log(GetMessage('SUPC_LE_UPD'), 'CL04');
				}
			}

			if ($errorMessage !== '')
			{
				CUpdateClient::AddMessage2Log('Error: ' . $errorMessage, 'UPD_ERROR');
				echo 'ERR' . $errorMessage;
				CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'N', 'DESCRIPTION' => $errorMessage]);
			}
			else
			{
				echo 'STP';
				echo count($arItemsUpdated) . '|';
				$bFirst = true;
				foreach ($arItemsUpdated as $key => $value)
				{
					CUpdateClient::AddMessage2Log('Updated: ' . $key . (($value <> '') ? '(' . $value . ')' : ''), 'UPD_SUCCESS');
					echo ($bFirst ? '' : ', ') . $key . (($value <> '') ? '(' . $value . ')' : '');
					$bFirst = false;
				}
			}
		}
	}
	else
	{
		CUpdateClient::AddMessage2Log('Error: ' . $errorMessage, 'UPD_ERROR');
		echo 'ERR' . $errorMessage;
		CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'N', 'DESCRIPTION' => $errorMessage]);
	}
}
else
{
	if (!CUpdateClient::GetNextStepHelpUpdates($errorMessage, LANG, $arRequestedHelps))
	{
		$errorMessage .= '[CL01] ' . GetMessage('SUPC_ME_LOAD') . '. ';
		CUpdateClient::AddMessage2Log(GetMessage('SUPC_ME_LOAD'), 'CL01');
	}

	$temporaryUpdatesDir = '';
	if ($errorMessage === '')
	{
		if (!CUpdateClient::UnGzipArchive($temporaryUpdatesDir, $errorMessage, true))
		{
			$errorMessage .= '[CL02] ' . GetMessage('SUPC_ME_PACK') . '. ';
			CUpdateClient::AddMessage2Log(GetMessage('SUPC_ME_PACK'), 'CL02');
		}
	}

	$arStepUpdateInfo = [];
	if ($errorMessage === '')
	{
		$arStepUpdateInfo = CUpdateClient::GetStepUpdateInfo($temporaryUpdatesDir, $errorMessage);
	}

	if ($errorMessage === '')
	{
		if (isset($arStepUpdateInfo['DATA']['#']['ERROR']))
		{
			for ($i = 0, $cnt = count($arStepUpdateInfo['DATA']['#']['ERROR']); $i < $cnt; $i++)
			{
				$errorMessage .= '[' . $arStepUpdateInfo['DATA']['#']['ERROR'][$i]['@']['TYPE'] . '] ' . $arStepUpdateInfo['DATA']['#']['ERROR'][$i]['#'];
			}
		}
	}

	$arItemsUpdated = [];
	if ($errorMessage === '')
	{
		if (isset($arStepUpdateInfo['DATA']['#']['ITEM']))
		{
			for ($i = 0, $cnt = count($arStepUpdateInfo['DATA']['#']['ITEM']); $i < $cnt; $i++)
			{
				$arItemsUpdated[$arStepUpdateInfo['DATA']['#']['ITEM'][$i]['@']['NAME']] = $arStepUpdateInfo['DATA']['#']['ITEM'][$i]['@']['VALUE'];
			}
		}
	}

	if ($errorMessage === '')
	{
		if (isset($arStepUpdateInfo['DATA']['#']['NOUPDATES']))
		{
			CUpdateClient::ClearUpdateFolder($_SERVER['DOCUMENT_ROOT'] . '/bitrix/updates/' . $temporaryUpdatesDir);
			CUpdateClient::AddMessage2Log('Finish - NOUPDATES', 'STEP');
			echo 'FIN';
			CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'Y', 'DESCRIPTION' => GetMessage('SUPC_HE_C_UPDATED')]);
		}
		else
		{
			if ($errorMessage === '')
			{
				if (!CUpdateClient::UpdateStepHelps($temporaryUpdatesDir, $errorMessage))
				{
					$errorMessage .= '[CL04] ' . GetMessage('SUPC_HE_UPD') . '. ';
					CUpdateClient::AddMessage2Log(GetMessage('SUPC_HE_UPD'), 'CL04');
				}
			}

			if ($errorMessage !== '')
			{
				CUpdateClient::AddMessage2Log('Error: ' . $errorMessage, 'UPD_ERROR');
				echo 'ERR' . $errorMessage;
				CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'N', 'DESCRIPTION' => $errorMessage]);
			}
			else
			{
				echo 'STP';
				echo count($arItemsUpdated) . '|';
				$bFirst = true;
				foreach ($arItemsUpdated as $key => $value)
				{
					CUpdateClient::AddMessage2Log('Updated: ' . $key . (($value <> '') ? '(' . $value . ')' : ''), 'UPD_SUCCESS');
					echo ($bFirst ? '' : ', ') . $key . (($value <> '') ? '(' . $value . ')' : '');
					$bFirst = false;
				}
			}
		}
	}
	else
	{
		CUpdateClient::AddMessage2Log('Error: ' . $errorMessage, 'UPD_ERROR');
		echo 'ERR' . $errorMessage;
		CControllerLog::Add(['CONTROLLER_MEMBER_ID' => 0, 'NAME' => 'SITE_UPDATE_KERNEL', 'STATUS' => 'N', 'DESCRIPTION' => $errorMessage]);
	}
}
/************************************/


if (!defined('UPD_INTERNAL_CALL') || constant('UPD_INTERNAL_CALL') !== 'Y')
{
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';
}
