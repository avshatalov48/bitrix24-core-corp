<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);

/* PROVIDER -> CONTROLLER -> PORTAL */

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

if (IsModuleInstalled('imbot'))
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imbot/handlers/controller.php");
}

CMain::FinalActions();
die();