<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION;

if (!$arParams["PRODUCT_CHANGE_EVENT_NAME"])
	$arParams["PRODUCT_CHANGE_EVENT_NAME"] = "";

$entityTypeID = $arParams['ENTITY_TYPE_ID'] = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : 0;
if($entityTypeID <= 0 && isset($_GET['entity_type_id']))
{
	$entityTypeID = $arParams['ENTITY_TYPE_ID'] = intval($_GET['entity_type_id']);
}
$arResult['ENTITY_TYPE_ID'] = $entityTypeID;

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_GET['entity_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_GET['entity_id']);
}
$arResult['ENTITY_ID'] = $entityID;


if($entityTypeID <= CCrmOwnerType::Undefined)
{
	ShowError(GetMessage('CRM_PRODUCT_LIST_OWNER_TYPE_NOT_DEFINED'));
	return;
}

if($entityTypeID !== CCrmOwnerType::Deal
	&& $entityTypeID !== CCrmOwnerType::Lead
	&& $entityTypeID !== CCrmOwnerType::Invoice)
{

	ShowError(GetMessage('CRM_PRODUCT_LIST_OWNER_TYPE_NOT_SUPPORTED'));
	return;
}

if($entityID <= 0)
{
	ShowError(GetMessage('CRM_PRODUCT_LIST_OWNER_ID_NOT_DEFINED'));
	return;
}

$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $userPerms))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_product_row_list';
}
$arResult['UID'] = $arParams['UID'];

$arResult['ITEMS'] = array();
$arResult['TAX_MODE'] = 'NONE';
if($entityTypeID === CCrmOwnerType::Deal)
{
	$dbRes = CCrmDeal::GetListEx(
		array(), array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false,
		array('TITLE', 'CURRENCY_ID', 'OPPORTUNITY')
	);

	$arOwner = $dbRes ? $dbRes->Fetch() : null;
	if($arOwner)
	{
		$arResult['TITLE'] = isset($arOwner['TITLE'])
			? $arOwner['TITLE'] : '';

		$arResult['CURRENCY_ID'] = isset($arOwner['CURRENCY_ID'])
			? $arOwner['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();

		$arResult['SUM'] = isset($arOwner['OPPORTUNITY'])
			? $arOwner['OPPORTUNITY'] : 0.0;
	}
	else
	{
		$arResult['TITLE'] = '';
		$arResult['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		$arResult['SUM'] = 0.0;
	}

	$productRows =  CCrmProductRow::LoadRows(CCrmOwnerTypeAbbr::Deal, $entityID);
	foreach($productRows as &$productRow)
	{
		$productRow['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($productRow['PRICE'], $arResult['CURRENCY_ID']);
		$arResult['ITEMS'][] = $productRow;
	}
	unset($productRow);

	$arResult['FORMATTED_SUM'] =
		CCrmCurrency::MoneyToString($arResult['SUM'], $arResult['CURRENCY_ID']);
}
elseif($entityTypeID === CCrmOwnerType::Lead)
{
	$dbRes = CCrmLead::GetListEx(
		array(), array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false,
		array('TITLE', 'CURRENCY_ID', 'OPPORTUNITY')
	);

	$arOwner = $dbRes ? $dbRes->Fetch() : null;
	if($arOwner)
	{
		$arResult['TITLE'] = isset($arOwner['TITLE'])
			? $arOwner['TITLE'] : '';

		$arResult['CURRENCY_ID'] = isset($arOwner['CURRENCY_ID'])
			? $arOwner['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();

		$arResult['SUM'] = isset($arOwner['OPPORTUNITY'])
			? $arOwner['OPPORTUNITY'] : 0.0;
	}
	else
	{
		$arResult['TITLE'] = '';
		$arResult['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		$arResult['SUM'] = 0.0;
	}

	$productRows =  CCrmProductRow::LoadRows(CCrmOwnerTypeAbbr::Lead, $entityID);
	foreach($productRows as &$productRow)
	{
		$productRow['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($productRow['PRICE'], $arResult['CURRENCY_ID']);
		$arResult['ITEMS'][] = $productRow;
	}
	unset($productRow);

	$arResult['FORMATTED_SUM'] =
		CCrmCurrency::MoneyToString($arResult['SUM'], $arResult['CURRENCY_ID']);
}
elseif($entityTypeID === CCrmOwnerType::Invoice)
{
	$dbRes = CCrmInvoice::GetList(
		array(), array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false,
		array('ORDER_TOPIC', 'CURRENCY', 'PRICE', 'TAX_VALUE')
	);

	$arOwner = $dbRes ? $dbRes->Fetch() : null;
	if($arOwner)
	{
		$arResult['TITLE'] = isset($arOwner['ORDER_TOPIC'])
			? $arOwner['ORDER_TOPIC'] : '';

		$arResult['CURRENCY_ID'] = isset($arOwner['CURRENCY'])
			? $arOwner['CURRENCY'] : CCrmCurrency::GetBaseCurrencyID();

		$arResult['SUM'] = isset($arOwner['PRICE'])
			? round(doubleval($arOwner['PRICE']), 2) : 0.0;

		$arResult['TAX_SUM'] = isset($arOwner['TAX_VALUE'])
			? round(doubleval($arOwner['TAX_VALUE']), 2) : 0.0;
	}
	else
	{
		$arResult['TITLE'] = '';
		$arResult['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		$arResult['SUM'] = 0.0;
		$arResult['TAX_SUM'] = 0.0;
	}

	$rows = CCrmInvoice::GetProductRows($entityID);
	foreach($rows as &$row)
	{
		$item = array(
			'PRODUCT_NAME' => isset($row['PRODUCT_NAME']) ? $row['PRODUCT_NAME'] : '',
			'PRODUCT_ID' => isset($row['PRODUCT_ID']) ? $row['PRODUCT_ID'] : '',
			'PRICE' => isset($row['PRICE']) ? round(doubleval($row['PRICE']), 2) : 0.0,
			'VAT_RATE' => isset($row['VAT_RATE']) ? round(doubleval($row['VAT_RATE']) * 100, 2) : 0.0,
			'QUANTITY' => isset($row['QUANTITY']) ? intval($row['QUANTITY']) : 0
		);
		$item['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($item['PRICE'], $arResult['CURRENCY_ID']);

		$arResult['ITEMS'][] = &$item;
		unset($item);
	}
	unset($row);

	// VAT - VAT ONLY
	// EXT - EXTENDED MODE WITH CUSTOM TAXES
	$arResult['TAX_MODE'] = CCrmTax::isVatMode() ? 'VAT' : 'EXT';
	if($arResult['TAX_MODE'] === 'VAT')
	{
		$arResult['VAT_SUM'] = isset($arResult['TAX_SUM']) ? $arResult['TAX_SUM'] : 0.0;
		$arResult['FORMATTED_VAT_SUM'] = CCrmCurrency::MoneyToString($arResult['VAT_SUM'], $arResult['CURRENCY_ID']);
		$arResult['FORMATTED_SUM_BRUTTO'] =
			CCrmCurrency::MoneyToString($arResult['SUM'], $arResult['CURRENCY_ID']);
	}
	else
	{
		$arResult['TAX_LIST'] = CCrmInvoice::getTaxList($entityID);
		foreach($arResult['TAX_LIST'] as &$taxInfo)
		{
			$taxInfo['FORMATTED_SUM'] = CCrmCurrency::MoneyToString($taxInfo['VALUE_MONEY'], $arResult['CURRENCY_ID']);
		}
		unset($taxInfo);

		$arResult['TAX_LIST_PERCENT_PRECISION'] = SALE_VALUE_PRECISION;
	}

	$arResult['FORMATTED_SUM_BRUTTO'] =
		CCrmCurrency::MoneyToString($arResult['SUM'], $arResult['CURRENCY_ID']);

	$arResult['FORMATTED_SUM_NETTO'] =
		CCrmCurrency::MoneyToString(($arResult['SUM'] - $arResult['TAX_SUM']), $arResult['CURRENCY_ID']);
}
else
{
	$arResult['TITLE'] = '';
	$arResult['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
	$arResult['SUM'] = 0.0;
}

$this->IncludeComponentTemplate();
