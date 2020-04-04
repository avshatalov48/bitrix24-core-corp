<? 
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php"); 
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/configs/currency/index.php");
global $APPLICATION;

$APPLICATION->SetTitle(GetMessage("TITLE"));
$APPLICATION->IncludeComponent(
	"bitrix:crm.currency", 
	".default", 
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/crm/configs/currency/",
	),
	false
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); 
?>
