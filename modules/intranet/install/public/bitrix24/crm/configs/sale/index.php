<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] == "Y")
{
	$APPLICATION->IncludeComponent(
		"bitrix:ui.sidepanel.wrapper",
		"",
		[
			"POPUP_COMPONENT_NAME" => "bitrix:crm.config.sale.settings",
			"POPUP_COMPONENT_TEMPLATE_NAME" => "",
			"USE_UI_TOOLBAR" => "Y",
		]
	);
}
else
{
	$APPLICATION->IncludeComponent("bitrix:crm.config.sale.settings", "", array(), false);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");