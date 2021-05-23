<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/telephony/configs.php");

$APPLICATION->SetTitle(GetMessage("VI_PAGE_CONFIGS_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrapper",
	"",
	array(
		"POPUP_COMPONENT_NAME" => "bitrix:voximplant.settings",
		"POPUP_COMPONENT_TEMPLATE_NAME" => "",
		"USE_PADDING" => false,
		"CLOSE_AFTER_SAVE" => true
	)
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
