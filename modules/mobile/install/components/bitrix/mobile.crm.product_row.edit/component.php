<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION;

if(!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
$arResult['CONTEXT_ID'] = $contextID;

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$uid = 'mobile_crm_product_row_edit';
}
else
{
	$uid = str_replace(array('#CONTEXT_ID#'), array($contextID), $uid);
}
$arResult['UID'] = $uid;

$productID = $arParams['PRODUCT_ID'] = isset($arParams['PRODUCT_ID']) ? intval($arParams['PRODUCT_ID']) : 0;
if($productID <= 0 && isset($_REQUEST['product_id']))
{
	$productID = intval($_REQUEST['product_id']);
}
$arResult['PRODUCT_ID'] = $productID;

if($productID <= 0)
{
	$arResult['PRODUCT_NAME'] = '';
}
else
{
	$dbProduct = CCrmProduct::GetList(array(), array('=ID' => $productID), array('NAME'));
	$product = $dbProduct ? $dbProduct->Fetch() : null;
	$arResult['PRODUCT_NAME'] = is_array($product) && isset($product['NAME']) ? $product['NAME'] : $productID;
}

$currencyID = $arParams['CURRENCY_ID'] = isset($arParams['CURRENCY_ID']) ? $arParams['CURRENCY_ID'] : '';
if($currencyID === '' && isset($_REQUEST['currency_id']))
{
	$currencyID = $_REQUEST['currency_id'];
}
if($currencyID === '')
{
	$currencyID = CCrmCurrency::GetBaseCurrencyID();
}
$arResult['CURRENCY_ID'] = $currencyID;

$price = $arParams['PRICE'] = isset($arParams['PRICE']) ? doubleval($arParams['PRICE']) : 0.0;
if($price <= 0 && isset($_REQUEST['price']))
{
	$price = doubleval($_REQUEST['price']);
}
$arResult['PRICE'] = $price;

$quantity = $arParams['QUANTITY'] = isset($arParams['QUANTITY']) ? intval($arParams['QUANTITY']) : 0;
if($quantity <= 0 && isset($_REQUEST['quantity']))
{
	$quantity = intval($_REQUEST['quantity']);
}
$arResult['QUANTITY'] = $quantity;

$arResult['SUM'] = $price * $quantity;
$arResult['FORMATTED_SUM'] = CCrmCurrency::MoneyToString($arResult['SUM'], $currencyID);


$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.product_row.edit/ajax.php?site_id=#SITE#&#SID#'
);

$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => bitrix_sessid_get())
);

$this->IncludeComponentTemplate();

