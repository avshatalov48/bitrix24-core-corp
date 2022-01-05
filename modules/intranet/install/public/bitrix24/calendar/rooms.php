<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
CModule::IncludeModule("intranet");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/calendar/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE_ROOMS"));

$APPLICATION->IncludeComponent(
	"bitrix:calendar.grid",
	"",
	Array(
		"CALENDAR_TYPE" => "location"
	)
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
