<?
require($_SERVER["DOCUMENT_ROOT"] . "/mobile/headers.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$APPLICATION->IncludeComponent("bitrix:mobile.data", "", Array(
		"START_PAGE" => SITE_DIR."mobile/index.php",
		"MENU_PAGE" => SITE_DIR."mobile/left.php"
	),
	false,
	Array("HIDE_ICONS" => "Y")
);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");