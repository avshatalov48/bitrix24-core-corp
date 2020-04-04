<? 
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php"); 
global $APPLICATION;
$APPLICATION->SetTitle(GetMessage("CRM_PAGE_TITLE"));
$APPLICATION->IncludeComponent(
	"bitrix:crm.currency", 
	".default", 
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/crm/currency/",	
	),
	false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); 
?>
