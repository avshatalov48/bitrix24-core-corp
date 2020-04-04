<?
/**
 * @global $APPLICATION
 */
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!CModule::IncludeModule("voximplant"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'VI_MODULE_NOT_INSTALLED'));
	CMain::FinalActions();
	die();
}

if (!check_bitrix_sessid())
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SESSION_ERROR'));
	CMain::FinalActions();
	die();
}

$action = (string)$_REQUEST['action'];
$params = (array)$_REQUEST['params'];

if($action == 'getTranscript')
{
	$callId = (string)$params['callId'];

	$APPLICATION->ShowAjaxHead();
	$APPLICATION->IncludeComponent('bitrix:voximplant.transcript.view',
		'.default',
		array(
			'CALL_ID' => $callId
		)
	);
	CAllMain::FinalActions();
	die();
}
else
{
	die('Unknown action');
}
