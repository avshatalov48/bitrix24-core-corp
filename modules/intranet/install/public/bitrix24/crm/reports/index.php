<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/reports/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE")/*"Воронка продаж"*/);
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.deal.funnel",
	"",
	Array(
	),
false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>