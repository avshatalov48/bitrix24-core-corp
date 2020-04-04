<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/** @global $APPLICATION CMain */
global $USER, $APPLICATION;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if (!$USER->IsAuthorized() || $_SERVER['REQUEST_METHOD'] != 'GET')
{
	ShowError(GetMessage('CRM_LOAD_DIALOG_ERROR'));
	return;
}

$jsEventsManagerId = isset($_GET['JS_EVENTS_MANAGER_ID'])? strval($_GET['JS_EVENTS_MANAGER_ID']) : '';
if ($jsEventsManagerId === '')
	return;

CUtil::JSPostUnescape();

$APPLICATION->ShowAjaxHead();

$APPLICATION->IncludeComponent(
	'bitrix:crm.product.search.dialog',
	'',
	array('JS_EVENTS_MANAGER_ID' => $jsEventsManagerId),
	false
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>