<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent(
	"bitrix:intranet.promo.page",
	"disk.app",
	array("PAGE" => "MACOS")
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");