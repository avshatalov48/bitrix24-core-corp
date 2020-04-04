<?
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (IsModuleInstalled('tasks'))
{
	$APPLICATION->IncludeComponent(
		"bitrix:webservice.server",
		"",
		array(
			'WEBSERVICE_NAME' => 'bitrix.webservice.tasks',
			'WEBSERVICE_CLASS' => 'CTasksWebService',
			'WEBSERVICE_MODULE' => 'tasks',
		),
		null, array('HIDE_ICONS' => 'Y')
	);
}

die();
?>