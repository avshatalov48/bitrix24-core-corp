<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/telephony/invoices.php");

$APPLICATION->SetTitle(GetMessage("VI_PAGE_INVOICES_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrapper",
	"",
	array(
		"POPUP_COMPONENT_NAME" => "bitrix:voximplant.invoice.list",
		"POPUP_COMPONENT_TEMPLATE_NAME" => "",
		"USE_PADDING" => false,
		"USE_UI_TOOLBAR" => "Y"
	)
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
