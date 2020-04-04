<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/product/index.php");
global $APPLICATION;
$APPLICATION->SetTitle(GetMessage("TITLE"));
$APPLICATION->IncludeComponent("bitrix:crm.product", ".default", array(
	"SEF_MODE" => "Y",
	"SEF_FOLDER" => "/crm/product/"
	),
	false
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>