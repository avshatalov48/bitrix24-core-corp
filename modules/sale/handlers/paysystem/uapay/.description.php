<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$host = $request->isHttps() ? "https" : "http";

$isAvailable = \Bitrix\Sale\PaySystem\Manager::HANDLER_AVAILABLE_TRUE;

$licensePrefix = \Bitrix\Main\Loader::includeModule("bitrix24") ? \CBitrix24::getLicensePrefix() : "";
if (IsModuleInstalled("bitrix24") && !in_array($licensePrefix, ["ua"]))
{
	$isAvailable = \Bitrix\Sale\PaySystem\Manager::HANDLER_AVAILABLE_FALSE;
}

$data = array(
	"NAME" => Loc::getMessage("SALE_HPS_UAPAY"),
	"SORT" => 500,
	"IS_AVAILABLE" => $isAvailable,
	"CODES" => array(
		"UAPAY_CLIENT_ID" => array(
			"NAME" => Loc::getMessage("SALE_HPS_UAPAY_CLIENT_ID"),
			"SORT" => 100,
			"GROUP" => "CONNECT_SETTINGS_UAPAY",
		),
		"UAPAY_SIGN_KEY" => array(
			"NAME" => Loc::getMessage("SALE_HPS_UAPAY_SIGN_KEY"),
			"DESCRIPTION" => Loc::getMessage("SALE_HPS_UAPAY_SIGN_KEY_DESC"),
			"SORT" => 200,
			"GROUP" => "CONNECT_SETTINGS_UAPAY",
		),
		"UAPAY_CALLBACK_URL" => array(
			"NAME" => Loc::getMessage("SALE_HPS_UAPAY_CALLBACK_URL"),
			"DESCRIPTION" => Loc::getMessage("SALE_HPS_UAPAY_CALLBACK_URL_DESC"),
			"SORT" => 300,
			"GROUP" => "CONNECT_SETTINGS_UAPAY",
			"DEFAULT" => array(
				"PROVIDER_KEY" => "VALUE",
				"PROVIDER_VALUE" => $host."://".$request->getHttpHost()."/bitrix/tools/sale_ps_result.php",
			)
		),
		"UAPAY_REDIRECT_URL" => array(
			"NAME" => Loc::getMessage("SALE_HPS_UAPAY_REDIRECT_URL"),
			"DESCRIPTION" => Loc::getMessage("SALE_HPS_UAPAY_REDIRECT_URL_DESC"),
			"SORT" => 400,
			"GROUP" => "CONNECT_SETTINGS_UAPAY",
			"DEFAULT" => array(
				"PROVIDER_KEY" => "VALUE",
				"PROVIDER_VALUE" => $host."://".$request->getHttpHost()."/bitrix/tools/sale_ps_success.php",
			)
		),
		"UAPAY_INVOICE_DESCRIPTION" => array(
			"NAME" => Loc::getMessage("SALE_HPS_UAPAY_INVOICE_DESCRIPTION"),
			"DESCRIPTION" => Loc::getMessage("SALE_HPS_UAPAY_INVOICE_DESCRIPTION_DESC"),
			"SORT" => 500,
			"GROUP" => "CONNECT_SETTINGS_UAPAY",
			"DEFAULT" => array(
				"PROVIDER_KEY" => "VALUE",
				"PROVIDER_VALUE" => Loc::getMessage("SALE_HPS_UAPAY_INVOICE_DESCRIPTION_TEMPLATE"),
			)
		),
		"UAPAY_TEST_MODE" => array(
			"NAME" => Loc::getMessage("SALE_HPS_UAPAY_TEST_MODE"),
			"DESCRIPTION" => Loc::getMessage("SALE_HPS_UAPAY_TEST_MODE_DESC"),
			"SORT" => 600,
			"GROUP" => "CONNECT_SETTINGS_UAPAY",
			"INPUT" => array(
				"TYPE" => "Y/N"
			),
		),
		"PS_CHANGE_STATUS_PAY" => array(
			"NAME" => Loc::getMessage("SALE_HPS_UAPAY_CHANGE_STATUS_PAY"),
			"SORT" => 700,
			"GROUP" => "GENERAL_SETTINGS",
			"INPUT" => array(
				"TYPE" => "Y/N"
			),
			"DEFAULT" => array(
				"PROVIDER_KEY" => "INPUT",
				"PROVIDER_VALUE" => "Y",
			)
		),
	)
);
