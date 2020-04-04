<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/start/index.php");
global $APPLICATION;
$APPLICATION->SetTitle(GetMessage("TITLE"));
$APPLICATION->IncludeComponent(
	"bitrix:crm.channel_tracker",
	"",
	array()
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>