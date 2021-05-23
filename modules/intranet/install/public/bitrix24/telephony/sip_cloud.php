<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/telephony/sip_cloud.php");

$APPLICATION->SetTitle(GetMessage("VI_PAGE_SIP_CLOUD_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:ui.sidepanel.wrapper",
	"",
	array(
		"POPUP_COMPONENT_NAME" => "bitrix:voximplant.config.sip",
		"POPUP_COMPONENT_TEMPLATE_NAME" => "",
		"POPUP_COMPONENT_PARAMS" => [
			"TYPE" => "cloud"
		],
		"USE_PADDING" => false
	)
);?>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
