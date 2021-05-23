<?php

use \Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('PUBLIC_AJAX_MODE', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

CUtil::JSPostUnescape();

if ( ! check_bitrix_sessid() )
	exit();

CModule::IncludeModule('tasks');

Loc::loadMessages(__FILE__);

$loggedInUser = (int) \Bitrix\Tasks\Util\User::getId();

CTaskAssert::assert(isset($_POST['action']));

$arReply = array();
$status  = 'success';
switch ($_POST['action'])
{
	case 'loadFilterConstructorJs':
		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:tasks.filter.v2',
			'constructor',
			array(
				'USER_ID'                 => $loggedInUser,
				'LOAD_TEMPLATE_INSTANTLY' => true
			)
		);
		exit();
	break;

	case 'getPresetDefinition':
		try
		{
			CTaskAssert::assert(isset($_POST['presetId']) && ($_POST['presetId'] > 0));
			CTaskAssert::assertLaxIntegers($_POST['presetId']);
			
			$oFilter = CTaskFilterCtrl::GetInstance($loggedInUser);
			$arPresetDefinition = $oFilter->exportFilterDataForJs($_POST['presetId']);

			if ($arPresetDefinition !== false)
				$arReply = array('presetData' => $arPresetDefinition);
			else
				$status  = 'fail';
		}
		catch (Exception $e)
		{
			$status = 'fail';
		}
	break;

	case 'createPreset':
	case 'replacePreset':
		try
		{
			CTaskAssert::assert(
				isset($_POST['presetData'])
				&& is_array($_POST['presetData'])
				&& isset($_POST['presetData']['Name'])
				&& isset($_POST['presetData']['Condition'])
			);

			$_POST['presetData']['Parent'] = CTaskFilterCtrl::ROOT_PRESET;

			$mode     = CTaskFilterCtrl::IMPORT_MODE_CREATE;
			$presetId = null;

			if ($_POST['action'] === 'replacePreset')
			{
				CTaskAssert::assert(
					isset($_POST['presetId'])
					&& CTaskAssert::isLaxIntegers($_POST['presetId'])
					&& ($_POST['presetId'] > 0)
				);
				
				$mode     = CTaskFilterCtrl::IMPORT_MODE_REPLACE;
				$presetId = (int) $_POST['presetId'];
			}

			$oFilter = CTaskFilterCtrl::getInstance($loggedInUser);
			$newPresetId = $oFilter->importFilterDataFromJs(
				$_POST['presetData'], $mode, $presetId
			);

			$arReply['newPresetId'] = $newPresetId;
		}
		catch (Exception $e)
		{
			$status = 'fail';
		}
	break;

	case 'removePreset':
		try
		{
			CTaskAssert::assert(isset($_POST['presetId']) && ($_POST['presetId'] > 0));
			CTaskAssert::assertLaxIntegers($_POST['presetId']);
			
			$oFilter = CTaskFilterCtrl::getInstance($loggedInUser);
			$arPresetDefinition = $oFilter->removePreset($_POST['presetId']);

			if ($arPresetDefinition !== false)
				$arReply = array('removedPresetId' => (int) $_POST['presetId']);
			else
				$status  = 'fail';
		}
		catch (Exception $e)
		{
			$status = 'fail';
		}
	break;

	default:
		CTaskAssert::assert(false);
	break;
}

$APPLICATION->RestartBuffer();
header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
echo CUtil::PhpToJsObject(
	array(
		'status' => $status,
		'reply'  => $arReply
	)
);
CMain::FinalActions(); // to make events work on bitrix24