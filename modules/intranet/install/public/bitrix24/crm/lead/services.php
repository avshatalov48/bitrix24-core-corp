<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/lead/services.php");
$APPLICATION->SetTitle(GetMessage("TITLE")/*"��� ������"*/);
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.lead.webservice",
	"",
	Array(
	),
false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>