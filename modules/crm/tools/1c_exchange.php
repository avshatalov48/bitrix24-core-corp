<?
define('BX_SESSION_ID_CHANGE', false);
define('BX_SKIP_POST_UNQUOTE', true);
define('NO_AGENT_CHECK', true);
define("STATISTIC_SKIP_ACTIVITY_CHECK", true);
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/** @global CMain $APPLICATION */
global $APPLICATION;

IncludeModuleLangFile(__FILE__);

$exch1cEnabled = COption::GetOptionString('crm', 'crm_exch1c_enable', 'N');
$exch1cEnabled = ($exch1cEnabled === 'Y');
if ($exch1cEnabled)
{
	if ($license_name = COption::GetOptionString("main", "~controller_group_name"))
	{
		preg_match("/(project|tf)$/is", $license_name, $matches);
		if (strlen($matches[0]) > 0)
			$exch1cEnabled = false;
	}
}

$err_msg = "";
if ($err_msg == "" && !$exch1cEnabled)
	$err_msg = "failure\n".GetMessage('CRM_EXCH1C_NOT_ENABLED');
if ($err_msg == "" && !CModule::IncludeModule('iblock'))
	$err_msg = "failure\n".GetMessage('IBLOCK_MODULE_NOT_INSTALLED');
if ($err_msg == "" && !CModule::IncludeModule('crm'))
	$err_msg = "failure\n".GetMessage('CRM_MODULE_NOT_INSTALLED');
if ($err_msg == "" && !CModule::IncludeModule('catalog'))
	$err_msg = "failure\n".GetMessage('CATALOG_MODULE_NOT_INSTALLED');
if ($err_msg == "" && !CModule::IncludeModule('sale'))
	$err_msg = "failure\n".GetMessage('SALE_MODULE_NOT_INSTALLED');
if ($err_msg == "" && !CCrmPerms::IsAuthorized())
	$err_msg = "failure\n".GetMessage('CRM_EXCH1C_AUTH_ERROR');
if ($err_msg == "")
	$crmPerms = new CCrmPerms($GLOBALS["USER"]->GetID());
if ($err_msg == "" && !CCrmPerms::IsAdmin() && !$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
	$err_msg = "failure\n".GetMessage('CRM_EXCH1C_PERMISSION_DENIED');
if ($err_msg == "")
	$type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : "";
if ($err_msg == "" && !in_array($type, array("sale", "catalog", "get_catalog"), true))
	$err_msg = "failure\n".GetMessage('CRM_EXCH1C_UNKNOWN_COMMAND_TYPE');

if ($err_msg != "")
{
	$APPLICATION->RestartBuffer();
	echo $err_msg;
	return;
}

function OnCrmIblockXmlIdMiss(&$arFields)
{
	global $APPLICATION;
	$APPLICATION->throwException(GetMessage('CRM_EXCH1C_UNKNOWN_XML_ID'));
	return false;
}

function On1CExchAfterIBlockElementAdd(&$arFields)
{
	// Create catalog records for all iblock elements
	$iblockElementId = intval($arFields['RESULT']);
	if ($iblockElementId > 0)
	{
		$catalogProduct = new CCatalogProduct();
		$catalogProduct->Add(array('ID' => $iblockElementId, 'QUANTITY' => 0));
	}
	return true;
}

$arUserGroupArray = $GLOBALS["USER"]->GetUserGroupArray();

if($type=="sale")
{
	$APPLICATION->IncludeComponent("bitrix:sale.export.1c", "", Array(
		"SITE_LIST" => COption::GetOptionString("sale", "1C_SALE_SITE_LIST", ""),
		"EXPORT_PAYED_ORDERS" => COption::GetOptionString("sale", "1C_EXPORT_PAYED_ORDERS", ""),
		"EXPORT_ALLOW_DELIVERY_ORDERS" => COption::GetOptionString("sale", "1C_EXPORT_ALLOW_DELIVERY_ORDERS", ""),
		"EXPORT_FINAL_ORDERS" => COption::GetOptionString("sale", "1C_EXPORT_FINAL_ORDERS", ""),
		"FINAL_STATUS_ON_DELIVERY" => COption::GetOptionString("sale", "1C_FINAL_STATUS_ON_DELIVERY", "F"),
		"REPLACE_CURRENCY" => COption::GetOptionString("sale", "1C_REPLACE_CURRENCY", ""),
		"GROUP_PERMISSIONS" => $arUserGroupArray,
		"USE_ZIP" => COption::GetOptionString("sale", "1C_SALE_USE_ZIP", "Y"),
		"EXPORT_FROM_CRM" => "Y"
		)
	);
}
elseif($type=="catalog")
{
	AddEventHandler("iblock", "OnBeforeIBlockAdd", "OnCrmIblockXmlIdMiss");
	AddEventHandler("iblock", "OnAfterIBlockElementAdd", "On1CExchAfterIBlockElementAdd");

	$APPLICATION->IncludeComponent("bitrix:catalog.import.1c", "", Array(
		"IBLOCK_TYPE" => COption::GetOptionString("catalog", "1C_IBLOCK_TYPE", "-"),
		"SITE_LIST" => array(COption::GetOptionString("catalog", "1C_SITE_LIST", "-")),
		"INTERVAL" => COption::GetOptionString("catalog", "1C_INTERVAL", "-"),
		"GROUP_PERMISSIONS" => $arUserGroupArray,
		"GENERATE_PREVIEW" => COption::GetOptionString("catalog", "1C_GENERATE_PREVIEW", "Y"),
		"PREVIEW_WIDTH" => COption::GetOptionString("catalog", "1C_PREVIEW_WIDTH", "100"),
		"PREVIEW_HEIGHT" => COption::GetOptionString("catalog", "1C_PREVIEW_HEIGHT", "100"),
		"DETAIL_RESIZE" => COption::GetOptionString("catalog", "1C_DETAIL_RESIZE", "Y"),
		"DETAIL_WIDTH" => COption::GetOptionString("catalog", "1C_DETAIL_WIDTH", "300"),
		"DETAIL_HEIGHT" => COption::GetOptionString("catalog", "1C_DETAIL_HEIGHT", "300"),
		"ELEMENT_ACTION" => COption::GetOptionString("catalog", "1C_ELEMENT_ACTION", "D"),
		"SECTION_ACTION" => COption::GetOptionString("catalog", "1C_SECTION_ACTION", "D"),
		"FILE_SIZE_LIMIT" => COption::GetOptionString("catalog", "1C_FILE_SIZE_LIMIT", 200*1024),
		"USE_CRC" => COption::GetOptionString("catalog", "1C_USE_CRC", "Y"),
		"USE_ZIP" => COption::GetOptionString("catalog", "1C_USE_ZIP", "Y"),
		"USE_OFFERS" => COption::GetOptionString("catalog", "1C_USE_OFFERS", "N"),
		"FORCE_OFFERS" => COption::GetOptionString("catalog", "1C_FORCE_OFFERS", "N"),
		"USE_IBLOCK_TYPE_ID" => COption::GetOptionString("catalog", "1C_USE_IBLOCK_TYPE_ID", "N"),
		"USE_IBLOCK_PICTURE_SETTINGS" => COption::GetOptionString("catalog", "1C_USE_IBLOCK_PICTURE_SETTINGS", "N"),
		"TRANSLIT_ON_ADD" => COption::GetOptionString("catalog", "1C_TRANSLIT_ON_ADD", "N"),
		"TRANSLIT_ON_UPDATE" => COption::GetOptionString("catalog", "1C_TRANSLIT_ON_UPDATE", "N"),
		"SKIP_ROOT_SECTION" => COption::GetOptionString("catalog", "1C_SKIP_ROOT_SECTION", "N"),
		)
	);
}
elseif($type=="get_catalog")
{
	$APPLICATION->IncludeComponent("bitrix:catalog.export.1c", "", Array(
		"IBLOCK_ID" => COption::GetOptionString("catalog", "1CE_IBLOCK_ID", ""),
		"INTERVAL" => COption::GetOptionString("catalog", "1CE_INTERVAL", "-"),
		"ELEMENTS_PER_STEP" => COption::GetOptionString("catalog", "1CE_ELEMENTS_PER_STEP", 100),
		"GROUP_PERMISSIONS" => $arUserGroupArray,
		"USE_ZIP" => COption::GetOptionString("catalog", "1CE_USE_ZIP", "Y"),
		)
	);
}

/*if (empty($err_msg))
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");*/
