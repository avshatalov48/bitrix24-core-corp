<?
define("NOT_CHECK_FILE_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (check_bitrix_sessid() && $USER->IsAuthorized())
{
	CModule::IncludeModule('intranet');
	CUtil::JSPostUnescape();

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'update';
	$site_id = $_REQUEST['site_id'];

	$res = array();

	if($action !== 'update')
	{
		$res = CIntranetPlanner::callAction($action, $site_id);
	}

	$arData = CIntranetPlanner::getData($site_id, true);

	if(is_array($res) && is_array($arData['DATA']))
	{
		$arData['DATA'] = array_merge($res, $arData['DATA']);
	}

	$arData['DATA']['FULL'] = true;

	Header('Content-Type: application/json; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arData['DATA']);
}
else
{
	echo GetMessage('main_include_decode_pass_sess');
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>