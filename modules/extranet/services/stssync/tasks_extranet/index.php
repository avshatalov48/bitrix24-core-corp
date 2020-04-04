<?
define('BX_SECURITY_SESSION_VIRTUAL', true);
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
define("IS_EXTRANET", true);
define("EXTRANET_NO_REDIRECT", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$APPLICATION->IncludeComponent(
	"bitrix:stssync.server",
	"",
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/stssync/tasks_extranet/",
		"REDIRECT_PATH" => '#PATH#/task/view/#ID#/',
		'WEBSERVICE_NAME' => 'bitrix.webservice.tasks',
		'WEBSERVICE_CLASS' => 'CTasksWebService',
		'WEBSERVICE_MODULE' => 'tasks',
	),
	null, array('HIDE_ICONS' => 'Y')
);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>