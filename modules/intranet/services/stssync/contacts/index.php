<?
define('BX_SECURITY_SESSION_VIRTUAL', true);
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$redirectPath = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", '/company/personal/user/#user_id#/', \CSite::GetDefSite());

$APPLICATION->IncludeComponent(
	"bitrix:stssync.server",
	"",
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/stssync/contacts/",
		"REDIRECT_PATH" => str_replace('#user_id#', '#ID#', $redirectPath),
		'WEBSERVICE_NAME' => 'bitrix.webservice.intranet.contacts',
		'WEBSERVICE_CLASS' => 'CIntranetContactsWS',
		'WEBSERVICE_MODULE' => 'intranet',
	),
	null, array('HIDE_ICONS' => 'Y')
);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>