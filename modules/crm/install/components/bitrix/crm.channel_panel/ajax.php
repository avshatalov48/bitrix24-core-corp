<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' SUPPORTED
 * SUPPORTED ACTIONS:
 * 'MARK_AS_ENABLED' - enable/disable panel
 */

/** @var \CMain $APPLICATION */

$currentUser = CCrmSecurityHelper::GetCurrentUser();
if (!$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
if(!function_exists('__CrmChannelPanelEndJsonResonse'))
{
	function __CrmChannelPanelEndJsonResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

CUtil::JSPostUnescape();
$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === '')
{
	__CrmChannelPanelEndJsonResonse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}
if($action === 'MARK_AS_ENABLED')
{
	$guid = isset($_POST['GUID']) ? $_POST['GUID'] : '';
	if($guid === '')
	{
		__CrmChannelPanelEndJsonResonse(array('ERROR'=>'GUID IS NOT DEFINED!'));
	}
	else
	{
		$enabled = !isset($_POST['ENABLED']) || strtoupper($_POST['ENABLED']) === 'Y';
		CBitrixComponent::includeComponentClass('bitrix:crm.channel_panel');
		CCrmChannelPanelComponent::markAsEnabled($guid, $enabled);
		__CrmChannelPanelEndJsonResonse(array('GUID' => $guid, 'ENABLED' => $enabled ? 'Y' : 'N'));
	}
}
?>
