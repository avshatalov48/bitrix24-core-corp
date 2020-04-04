<?
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php"); 

if (COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule("calendar"))
{
	$APPLICATION->IncludeComponent(
		"bitrix:webservice.server",
		"",
		array(
			'WEBSERVICE_NAME' => 'bitrix.webservice.calendar',
			'WEBSERVICE_CLASS' => 'CCalendarWebService',
			'WEBSERVICE_MODULE' => 'calendar',
		),
		null, array('HIDE_ICONS' => 'Y')
	);
}
else
{
	$APPLICATION->IncludeComponent(
		"bitrix:webservice.server",
		"",
		array(
			'WEBSERVICE_NAME' => 'bitrix.webservice.intranet.calendar',
			'WEBSERVICE_CLASS' => 'CIntranetCalendarWS',
			'WEBSERVICE_MODULE' => 'intranet',
		),
		null, array('HIDE_ICONS' => 'Y')
	);
}
die();
?>