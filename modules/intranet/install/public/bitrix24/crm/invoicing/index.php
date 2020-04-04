<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/invoicing/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.invoice.invoicing",
	".default",
	Array()
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>