<?
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php"); 

$APPLICATION->IncludeComponent(
	"bitrix:webservice.server",
	"",
	array(
		'WEBSERVICE_NAME' => 'bitrix.webservice.intranet.contacts',
		'WEBSERVICE_CLASS' => 'CIntranetContactsWS',
		'WEBSERVICE_MODULE' => 'intranet',
	),
	null, array('HIDE_ICONS' => 'Y')
);

die();
?>