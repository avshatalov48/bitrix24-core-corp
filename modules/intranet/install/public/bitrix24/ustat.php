<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent(
	"bitrix:intranet.ustat",
	"",
	Array(
		"BY" => $_REQUEST['BY'], //$arParams["BY"], // user | department
		"BY_ID" => $_REQUEST["BY_ID"], // [USER_ID] | [DEPARTMENT_ID]
		"PERIOD" => $_REQUEST["PERIOD"], // today, week, month, year
		"SECTION" => $_REQUEST["SECTION"], // null | TASKS | CRM | etc.
	),
	false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");