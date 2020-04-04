<?
define('BX_SECURITY_SESSION_VIRTUAL', true);
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$APPLICATION->IncludeComponent(
	"bitrix:stssync.server",
	"",
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/stssync/contacts_crm/",
		"REDIRECT_PATH" => str_replace('#contact_id#', '#ID#', COption::GetOptionString('crm', 'path_to_contact_show', '/crm/contact/show/#contact_id#/')),
		'WEBSERVICE_NAME' => 'bitrix.crm.contact.webservice',
		'WEBSERVICE_CLASS' => 'CCrmContactWS',
		'WEBSERVICE_MODULE' => 'crm',
	),
	null, array('HIDE_ICONS' => 'Y')
);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>