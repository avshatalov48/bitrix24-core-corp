<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


$APPLICATION->IncludeComponent(
	"bitrix:intranet.ustat.department",
	"",
	Array(
		"DEPARTMENT_ID" => $arParams["BY_ID"],
		"PERIOD" => $arParams["PERIOD"], // today, week, month, year
		"SECTION" => $arParams["SECTION"], // null | TASKS | CRM | etc.
	),
	false
);
