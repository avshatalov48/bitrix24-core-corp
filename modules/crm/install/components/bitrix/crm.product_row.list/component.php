<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION, $USER, $DB;

$arParams['PATH_TO_PRODUCT_EDIT'] = isset($arParams['PATH_TO_PRODUCT_EDIT']) ? $arParams['PATH_TO_PRODUCT_EDIT'] : '';
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');

if (\Bitrix\Main\Loader::includeModule('catalog') && \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isFullCatalogEnabled())
{
	$catalogId = CCrmCatalog::EnsureDefaultExists();
	$arParams['PATH_TO_PRODUCT_SHOW'] = "/crm/catalog/{$catalogId}/product/#product_id#/";
}
else
{
	$arParams['PATH_TO_PRODUCT_SHOW'] = isset($arParams['PATH_TO_PRODUCT_SHOW']) ? $arParams['PATH_TO_PRODUCT_SHOW'] : '';
	$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
}

//OWNER_ID for new entities is zero
$ownerID = $this->getOwnerId();

$ownerType = $this->getOwnerType();
$ownerName = CCrmProductRow::ResolveOwnerTypeName($ownerType);
if($ownerName === '')
{
	ShowError(GetMessage('CRM_UNSUPPORTED_OWNER_TYPE', array('#OWNER_TYPE#' => $ownerType)));
	return;
}
// Check permissions (READ by default)
$permissionType = isset($arParams['PERMISSION_TYPE']) ? (string)$arParams['PERMISSION_TYPE'] : 'READ';
$permissionEntityType = $this->getPermissionEntityType($ownerName, $ownerID);

$arResult['PERMISSION_ENTITY_TYPE'] = $permissionEntityType;
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmAuthorizationHelper::CheckReadPermission($permissionEntityType, $ownerID, $userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

/** @var \CBitrixComponent $this */
$arResult['COMPONENT_ID'] = $this->randString();

$arResult['OWNER_TYPE'] = $ownerType;
$arResult['OWNER_ID'] = $ownerID;

$arResult['READ_ONLY'] = $permissionType == 'READ';
$arResult['INIT_EDITABLE'] = isset($arParams['INIT_EDITABLE']) ? $arParams['INIT_EDITABLE'] === 'Y' : false;
$arResult['HIDE_MODE_BUTTON'] = isset($arParams['HIDE_MODE_BUTTON']) ? $arParams['HIDE_MODE_BUTTON'] === 'Y' : false;

$arResult['HIDE_ALL_TAXES'] = isset($arParams['HIDE_ALL_TAXES']) ? ($arParams['HIDE_ALL_TAXES'] === 'Y') : false;
$arResult['ALLOW_TAX'] = isset($arParams['ALLOW_TAX']) ? ($arParams['ALLOW_TAX'] === 'Y') : CCrmTax::isVatMode();
$arResult['ALLOW_TAX'] = $arResult['ALLOW_TAX'] && !$arResult['HIDE_ALL_TAXES'];
$arResult['ALLOW_LD_TAX'] = isset($arParams['ALLOW_LD_TAX']) ? ($arParams['ALLOW_LD_TAX'] === 'Y') : CCrmTax::isTaxMode();
$arResult['ALLOW_LD_TAX'] = $arResult['ALLOW_LD_TAX'] || $arResult['HIDE_ALL_TAXES'];
$arResult['LOCATION_ID'] = isset($arParams['LOCATION_ID']) ? $arParams['LOCATION_ID'] : '';

$arResult['PRODUCT_ROW_TAX_UNIFORM'] = (COption::GetOptionString('crm', 'product_row_tax_uniform', 'Y') === 'Y');

$arResult['INVOICE_MODE'] = ($ownerType === 'I');
$arResult['HIDE_TAX_INCLUDED_COLUMN'] = false;
$arResult['CATALOG_TYPE_ID'] = CCrmCatalog::GetCatalogTypeID();

$arResult['USE_ASYNC_ADD_PRODUCT'] = isset($arParams['USE_ASYNC_ADD_PRODUCT']) ? $arParams['USE_ASYNC_ADD_PRODUCT'] == 'Y' : false;

// copy flag
$bCopy = ($arParams['COPY_FLAG'] === 'Y') ? true : false;

// Check currency (national currency by default)
$currencyID =  $arResult['CURRENCY_ID'] =  isset($arParams['CURRENCY_ID']) ? (string)$arParams['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
$currency = CCrmCurrency::GetByID($currencyID);
if(!$currency)
{
	ShowError(GetMessage('CRM_CURRENCY_IS_NOT_FOUND', array('#CURRENCY_ID#' => $currencyID)));
	return;
}

$arResult['CURRENCY_FORMAT'] = CCrmCurrency::GetCurrencyFormatString($currencyID);

//$exchRate = $arResult['EXCH_RATE'] = isset($arParams['EXCH_RATE']) ? (double)$arParams['EXCH_RATE'] : 1.0;
//$arResult['CURRENCY_DISPLAY_NAME'] = $currency['ID']; //ID is ISO 4217

// Prepare source data
if(isset($arParams['PRODUCT_ROWS']) && is_array($arParams['PRODUCT_ROWS']))
{
	$arResult['PRODUCT_ROWS'] = $arParams['PRODUCT_ROWS'];
	foreach($arResult['PRODUCT_ROWS'] as &$arProdRow)
	{
		$productID = intval($arProdRow['PRODUCT_ID']);
		if(isset($arProdRow['PRODUCT_NAME']))
		{
			continue;
		}

		$dbRes = CCrmProduct::GetList(
			array(),
			array('ID' => $productID),
			array('NAME')
		);

		$arProdRow['PRODUCT_NAME'] =
			is_array($arRes = $dbRes->Fetch()) ? $arRes['NAME'] : '['.strval($productID).']';
	}
	unset($arProdRow);
}
else
{
	if ($arResult['INVOICE_MODE'])
		$arResult['PRODUCT_ROWS'] = CCrmInvoice::GetProductRows($ownerID);
	else
		$arResult['PRODUCT_ROWS'] = $ownerID > 0 ? CCrmProductRow::LoadRows($ownerType, $ownerID) : array();
}

if ($bCopy)
{
	foreach ($arResult['PRODUCT_ROWS'] as &$row)
		if (isset($row['ID']))
			$row['ID'] = 0;
	unset($row);
}

// Determine person type
$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
$personTypeId = 0;
$arResult['CLIENT_SELECTOR_ID'] = isset($arParams['CLIENT_SELECTOR_ID']) ? $arParams['CLIENT_SELECTOR_ID'] : 'CLIENT';
$arResult['CLIENT_TYPE_NAME'] = "CONTACT";
if (isset($arParams['PERSON_TYPE_ID']) && isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
	$personTypeId = intval($arParams['PERSON_TYPE_ID']);
if ($personTypeId > 0)
{
	if ($personTypeId === intval($arPersonTypes['COMPANY']))
		$arResult['CLIENT_TYPE_NAME'] = "COMPANY";
	elseif ($personTypeId !== intval($arPersonTypes['CONTACT']))
		$personTypeId = 0;
}

// Prepare tax list
$taxList = array();
if ($arResult['ALLOW_LD_TAX'])
{
	if ($arResult['INVOICE_MODE'])
	{
		$taxList = CCrmInvoice::getTaxList($ownerID);
		if (!is_array($taxList))
			$taxList = array();
		foreach ($taxList as &$taxInfo)
		{
			$taxInfo['NAME'] = '';
			if (isset($taxInfo['TAX_NAME']))
			{
				$taxInfo['NAME'] = $taxInfo['TAX_NAME'];
				unset($taxInfo['TAX_NAME']);
			}
		}
		unset($taxInfo);
	}
	else
	{
		$totalInfo = CCrmProductRow::LoadTotalInfo($ownerType, $ownerID);
		$taxList = is_array($totalInfo['TAX_LIST']) ? $totalInfo['TAX_LIST'] : array();
	}
	$arResult['TAX_LIST_PERCENT_PRECISION'] = defined("SALE_VALUE_PRECISION") ? SALE_VALUE_PRECISION : 2;
}

// Prepare totals
$totalSum = 0.0;
$totalTax = 0.0;
$totalDiscount = 0.0;
foreach($arResult['PRODUCT_ROWS'] as &$row)
{
	// invoice specific
	if ($arResult['INVOICE_MODE'])
	{
		$row['ID'] = isset($row['ID']) ? (int)$row['ID'] : 0;
		$row['OWNER_ID'] = $row['ORDER_ID'];
		$row['OWNER_TYPE'] = $ownerType;
		$row['PRODUCT_ID'] = isset($row['PRODUCT_ID']) ? (int)$row['PRODUCT_ID'] : 0;
		$row['PRODUCT_NAME'] = isset($row['PRODUCT_NAME']) ? strval($row['PRODUCT_NAME']) : '';
		$row['ORIGINAL_PRODUCT_NAME'] = $row['PRODUCT_NAME'];
		$row['PRICE'] = round((double)$row['PRICE'], 2);

		if(isset($row['VAT_INCLUDED']) && $row['VAT_INCLUDED'] === 'N')
		{
			$exclusivePrice = $row['PRICE'];
			$row['PRICE_EXCLUSIVE'] = $exclusivePrice;
			$row['PRICE'] = round(
				CCrmProductRow::CalculateInclusivePrice($exclusivePrice, (100 * $row['VAT_RATE'])), 2
			);
		}
		else
		{
			$row['PRICE_EXCLUSIVE'] = round(
				CCrmProductRow::CalculateExclusivePrice($row['PRICE'], (100 * $row['VAT_RATE'])), 2
			);
		}

		$row['QUANTITY'] = round((double)$row['QUANTITY'], 4);
		$row['CUSTOMIZED'] = 'Y';
		$row['TAX_RATE'] = round((double)$row['VAT_RATE'] * 100, 2);
		$row['TAX_INCLUDED'] = (isset($row['VAT_INCLUDED']) && $row['VAT_INCLUDED'] === 'N') ? 'N' : 'Y';
		$row['SORT'] = isset($row['SORT']) ? (int)$row['SORT'] : 0;
		$row['DISCOUNT_TYPE_ID'] = \Bitrix\Crm\Discount::MONETARY;

		$row['DISCOUNT_SUM'] = round((double)$row['DISCOUNT_PRICE'], 2);
		$row['DISCOUNT_RATE'] = \Bitrix\Crm\Discount::calculateDiscountRate(
			($row['PRICE_EXCLUSIVE'] + $row['DISCOUNT_SUM']),
			$row['PRICE_EXCLUSIVE']
		);
		$ary['MEASURE_CODE'] = isset($row['MEASURE_CODE']) ? (int)$row['MEASURE_CODE'] : 0;
		$ary['MEASURE_NAME'] = isset($row['MEASURE_NAME']) ? $row['MEASURE_NAME'] : '';
		unset ($row['ORDER_ID'], $row['NAME'], $row['DISCOUNT_PRICE'], $row['VAT_RATE'], $row['CUSTOM_PRICE']);
	}

	if ($arResult['ALLOW_LD_TAX'])
	{
		$row['PRICE'] = CCrmProductRow::CalculateExclusivePrice($row['PRICE'], $row['TAX_RATE']);
		$row['TAX_RATE'] = 0.0;
		$row['TAX_INCLUDED'] = 'N';
	}

	if(!isset($row['PRICE_NETTO']) || $row['PRICE_NETTO'] == 0.0)
	{
		$discountTypeID = (int)$row['DISCOUNT_TYPE_ID'];
		if($discountTypeID === \Bitrix\Crm\Discount::MONETARY)
		{
			$row['PRICE_NETTO'] = $row['PRICE_EXCLUSIVE'] + $row['DISCOUNT_SUM'];
		}
		else
		{
			$discoutRate = (double)$row['DISCOUNT_RATE'];
			$discoutSum = $discoutRate < 100
				? \Bitrix\Crm\Discount::calculateDiscountSum($row['PRICE_EXCLUSIVE'], $discoutRate)
				: (double)$row['DISCOUNT_SUM'];
			$row['PRICE_NETTO'] = $row['PRICE_EXCLUSIVE'] + $discoutSum;
		}
	}

	if(!isset($row['PRICE_BRUTTO']) || $row['PRICE_BRUTTO'] == 0.0)
	{
		if ($arResult['INVOICE_MODE']
			&& isset($row['VAT_INCLUDED'])
			&& $row['VAT_INCLUDED'] !== 'N'
			&& $row['DISCOUNT_SUM'] == 0.0)
		{
			$row['PRICE_BRUTTO'] = $row['PRICE'];
		}
		else
		{
			$row['PRICE_BRUTTO'] = CCrmProductRow::CalculateInclusivePrice($row['PRICE_NETTO'], $row['TAX_RATE']);
		}
	}

	$totalDiscount += round($row['QUANTITY'] * $row['DISCOUNT_SUM'], 2);
}
unset($row);

if(count($arResult['PRODUCT_ROWS']) > 0)
{
	$enableSaleDiscount = false;
	$calcOptions = array();
	if ($arResult['ALLOW_LD_TAX'])
	{
		$calcOptions['ALLOW_LD_TAX'] = 'Y';
		$calcOptions['LOCATION_ID'] = isset($arParams['LOCATION_ID']) ? $arParams['LOCATION_ID'] : '';
	}

	$result = CCrmSaleHelper::Calculate($arResult['PRODUCT_ROWS'], $currencyID, $personTypeId, $enableSaleDiscount, SITE_ID, $calcOptions);

	if (is_array($result['TAX_LIST']))
	{
		$taxList = $result['TAX_LIST'];
	}
	$totalSum = isset($result['PRICE']) ? round((double)$result['PRICE'], 2) : 0.0;
	$totalTax = isset($result['TAX_VALUE']) ? round((double)$result['TAX_VALUE'], 2) : 0.0;
}
$arResult['TAX_LIST'] = $taxList;
$arResult['TOTAL_DISCOUNT'] = $totalDiscount;
$arResult['TOTAL_SUM'] = $totalSum;
$arResult['TOTAL_TAX'] = $totalTax;
$arResult['TOTAL_BEFORE_TAX'] = round($arResult['TOTAL_SUM'] - $arResult['TOTAL_TAX'], 2);
$arResult['TOTAL_BEFORE_DISCOUNT'] = $arResult['TOTAL_BEFORE_TAX'] + $arResult['TOTAL_DISCOUNT'];
unset($totalSum, $totalTax);

$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
$arResult['PREFIX'] = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
if($arResult['PREFIX'] === '')
{
	$arResult['PREFIX'] = ($ownerID > 0 ? mb_strtolower($ownerName).'_'.strval($ownerID) : 'new_'.mb_strtolower($ownerName)).'_product_editor';
}

$arResult['ID'] = isset($arParams['ID']) ? $arParams['ID'] : '';
if($arResult['ID'] === '')
{
	$arResult['ID'] = $arResult['PREFIX'];
}

//$arResult['CONTAINER_CLASS'] = htmlspecialcharsbx(strtolower($ownerName).'-product-rows');
$arResult['PRODUCT_DATA_FIELD_NAME'] = isset($arParams['PRODUCT_DATA_FIELD_NAME']) ? $arParams['PRODUCT_DATA_FIELD_NAME'] : 'PRODUCT_ROW_DATA';
$arResult['ENABLE_CUSTOM_PRODUCTS'] = isset($arParams['ENABLE_CUSTOM_PRODUCTS']) ? mb_strtoupper($arParams['ENABLE_CUSTOM_PRODUCTS']) === 'Y' : true;
$arResult['ENABLE_RAW_CATALOG_PRICING'] = !isset($arParams['ENABLE_RAW_CATALOG_PRICING']) || mb_strtoupper($arParams['ENABLE_RAW_CATALOG_PRICING']) === 'Y';

$arResult['TAX_INFOS'] = $arResult['ALLOW_TAX'] ? CCrmTax::GetVatRateInfos() : array();

$arResult['ENABLE_TAX'] = isset($arParams['ENABLE_TAX']) ? ($arParams['ENABLE_TAX'] === 'Y') : false;
$arResult['ENABLE_DISCOUNT'] = isset($arParams['ENABLE_DISCOUNT']) ? ($arParams['ENABLE_DISCOUNT'] === 'Y') : false;
$arResult['ENABLE_MODE_CHANGE'] = isset($arParams['ENABLE_MODE_CHANGE']) ? ($arParams['ENABLE_MODE_CHANGE'] === 'Y') : true;
$arResult['INIT_LAYOUT'] = isset($arParams['INIT_LAYOUT']) ? ($arParams['INIT_LAYOUT'] === 'Y') : true;

$settings = array();
if ($ownerID > 0)
{
	$settings = CCrmProductRow::LoadSettings($ownerType, $ownerID);
	if (isset($settings['ENABLE_TAX']))
		$arResult['ENABLE_TAX'] = (bool)$settings['ENABLE_TAX'];
	if (isset($settings['ENABLE_DISCOUNT']))
		$arResult['ENABLE_DISCOUNT'] = (bool)$settings['ENABLE_DISCOUNT'];
}

$arResult['SITE_ID'] = SITE_ID;
$arResult['CAN_ADD_PRODUCT'] = CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions);

if (!$arResult['USE_ASYNC_ADD_PRODUCT'])
{
	$arResult['PRODUCT_CREATE_DLG_VISIBLE_FIELDS'] = $this->getCreateDialogVisibleFields();

	$arResult['PRODUCT_CREATE_DLG_SETTINGS'] = $this->getCreateDialogSettings(
		$arResult['CURRENCY_ID'],
		$arResult['PRODUCT_CREATE_DLG_VISIBLE_FIELDS'],
		$arResult['ALLOW_TAX']
	);

	$arResult['PRODUCT_PROPS_USER_TYPES'] = $this->getProductPropsTypes();
	$arResult['PRODUCT_PROPS'] = $this->getProductProps($arResult['PRODUCT_PROPS_USER_TYPES']);
}

/** @var CBitrixComponent $this */
$this->IncludeComponentTemplate();