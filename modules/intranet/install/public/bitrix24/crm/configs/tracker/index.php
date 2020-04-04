<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/configs/tracker/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));

$APPLICATION->IncludeComponent(
		"bitrix:crm.config.tracker",
		".default"
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>