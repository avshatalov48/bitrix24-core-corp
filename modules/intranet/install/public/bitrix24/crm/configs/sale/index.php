<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if ($_REQUEST["IFRAME"] == "Y")
{
	$APPLICATION->IncludeComponent(
		"bitrix:crm.webform.popup",
		"",
		array(
			"POPUP_COMPONENT_NAME" => "bitrix:crm.config.sale.settings",
			"POPUP_COMPONENT_TEMPLATE_NAME" => ""
		)
	);
}
else
{
	$APPLICATION->IncludeComponent("bitrix:crm.config.sale.settings", "", array(), false);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");