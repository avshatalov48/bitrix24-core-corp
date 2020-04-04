<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}
if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

global $APPLICATION, $USER, $DB;

$arParams['PATH_TO_PRODUCT_EDIT'] = isset($arParams['PATH_TO_PRODUCT_EDIT']) ? $arParams['PATH_TO_PRODUCT_EDIT'] : '';
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');

//OWNER_ID for new entities is zero
$ownerID = isset($arParams['OWNER_ID']) ? (int)$arParams['OWNER_ID'] : 0;

// Check owner type (DEAL by default)
$ownerType = isset($arParams['OWNER_TYPE']) ? (string)$arParams['OWNER_TYPE'] : 'I';
$ownerName = '';
if($ownerType == 'I')
{
	$ownerName = 'INVOICE';
}
else
{
	ShowError(GetMessage('CRM_UNSUPPORTED_OWNER_TYPE', array('#OWNER_TYPE#' => $ownerType)));
	return;
}

// copy flag
$bCopy = ($arParams['COPY_FLAG'] === 'Y') ? true : false;

// Check permissions (READ by default)
$permissionType = isset($arParams['PERMISSION_TYPE']) ? (string)$arParams['PERMISSION_TYPE'] : 'READ';
$perms = new CCrmPerms($USER->GetID());
if ($perms->HavePerm($ownerName, BX_CRM_PERM_NONE, $permissionType))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}
$arResult['CAN_ADD_PRODUCT'] = $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arResult['OWNER_TYPE'] = $ownerType;
$arResult['OWNER_ID'] = $ownerID;

$arResult['READ_ONLY'] = $permissionType == 'READ';

$arResult['VAT_MODE'] = CCrmTax::isVatMode();

// Check currency (national currency by default)
$currencyID =  $arResult['CURRENCY_ID'] =  isset($arParams['CURRENCY_ID']) ? (string)$arParams['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
$currency = CCrmCurrency::GetByID($currencyID);
if(!$currency)
{
	ShowError(GetMessage('CRM_CURRENCY_IS_NOT_FOUND', array('#CURRENCY_ID#' => $currencyID)));
	return;
}

$arResult['CURRENCY_FORMAT'] = CCrmCurrency::GetCurrencyFormatString($currencyID);

// Prepare source data
if(isset($arParams['PRODUCT_ROWS']) && is_array($arParams['PRODUCT_ROWS']))
	$arResult['PRODUCT_ROWS'] = $arParams['PRODUCT_ROWS'];
else
	$arResult['PRODUCT_ROWS'] = CCrmInvoice::GetProductRows($ownerID);

if ($bCopy)
{
	foreach ($arResult['PRODUCT_ROWS'] as &$row)
		if (isset($row['ID']))
			$row['ID'] = 0;
	unset($row);
}

// Prepare tax list
if (!$arResult['VAT_MODE'])
{
	$arResult['TAX_LIST'] = CCrmInvoice::getTaxList($ownerID);
	$arResult['TAX_LIST_PERCENT_PRECISION'] = SALE_VALUE_PRECISION;
}

// Prepare sum total
$sumTotal = 0.0;
foreach($arResult['PRODUCT_ROWS'] as $row)
{
	if(!isset($row['PRICE']) || !isset($row['QUANTITY']))
	{
		continue;
	}

	$sumTotal += doubleval($row['PRICE']) * intval($row['QUANTITY']);
}

$arResult['TAX_VALUE'] = isset($arParams['TAX_VALUE']) ? $arParams['TAX_VALUE'] : 0.00;
$arResult['SUM_TOTAL'] = isset($arParams['SUM_TOTAL']) ? $arParams['SUM_TOTAL'] : round($sumTotal, 2);
$arResult['TAX_VALUE_WT'] = round(doubleval($arParams['SUM_TOTAL'] - doubleval($arParams['TAX_VALUE'])), 2);

//SAVING MODE. ONSUBMIT: SAVE ALL PRODUCT ROWS ON SUBMIT, ONCHANGE: SAVE PRODUCT ROWS AFTER EVERY CHANGE (AJAX)
$arResult['SAVING_MODE'] = isset($arParams['SAVING_MODE']) ? strtoupper($arParams['SAVING_MODE']) : 'ONSUBMIT';
if($arResult['SAVING_MODE'] != 'ONSUBMIT' && $arResult['SAVING_MODE'] != 'ONCHANGE')
{
	$arResult['SAVING_MODE'] = 'ONSUBMIT';
}

$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['PREFIX'] = htmlspecialcharsbx($ownerID > 0 ? strtolower($ownerName).'_'.strval($ownerID) : 'new_'.strtolower($ownerName));
$arResult['CONTAINER_CLASS'] = htmlspecialcharsbx(strtolower($ownerName).'-product-rows');
$arResult['ROW_CLASS'] = '';
$arResult['PRODUCT_DATA_FIELD_NAME'] = isset($arParams['PRODUCT_DATA_FIELD_NAME']) ? $arParams['PRODUCT_DATA_FIELD_NAME'] : 'PRODUCT_ROW_DATA';

// crmProductCreateDialog dialog settings
$bVatMode = CCrmTax::isVatMode();
$arResult['INVOICE_PRODUCT_CREATE_DLG_SETTINGS'] = array(
	'url' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_PRODUCT_EDIT'],
			array('product_id' => 0)
		),
	'sessid' => bitrix_sessid(),
	'messages' => array(
		'dialogTitle' => GetMessage('CRM_PRODUCT_CREATE'),
		'waitMessage' => GetMessage('CRM_PRODUCT_CREATE_WAIT'),
		'ajaxError' => GetMessage('CRM_PRODUCT_CREATE_AJAX_ERR'),
		'buttonCreateTitle' => GetMessage('CRM_BUTTON_CREATE_TITLE'),
		'buttonCancelTitle' => GetMessage('CRM_BUTTON_CANCEL_TITLE'),
		'NAME' => GetMessage('CRM_FIELD_NAME'),
		'DESCRIPTION' => GetMessage('CRM_FIELD_DESCRIPTION'),
		'ACTIVE' => GetMessage('CRM_FIELD_ACTIVE'),
		'CURRENCY' => GetMessage('CRM_FIELD_CURRENCY'),
		'PRICE' => GetMessage('CRM_FIELD_PRICE'),
		'VAT_ID' => GetMessage('CRM_FIELD_VAT_ID'),
		'VAT_INCLUDED' => GetMessage('CRM_FIELD_VAT_INCLUDED'),
		'SECTION_ID' => GetMessage('CRM_FIELD_SECTION'),
		'SORT' => GetMessage('CRM_FIELD_SORT')
	),
	'fields' => array(
		array('textCode' => 'NAME', 'type' => 'text', 'maxLength' => 255, 'value' => '', 'skip' => 'N'),
		array('textCode' => 'DESCRIPTION', 'type' => 'textarea', 'maxLength' => 7500, 'value' => '', 'skip' => 'N'),
		array('textCode' => 'ACTIVE', 'type' => 'checkbox', 'value' => 'Y', 'skip' => 'N'),
		array('textCode' => 'CURRENCY', 'type' => 'select', 'value' => CCrmCurrency::GetBaseCurrencyID(),
			'items' => CCrmViewHelper::prepareSelectItemsForJS(CCrmCurrencyHelper::PrepareListItems()), 'skip' => 'N'),
		array('textCode' => 'PRICE', 'type' => 'text', 'maxLength' => 21, 'value' => '0.00', 'skip' => 'N'),
		array('textCode' => 'VAT_ID', 'type' => 'select', 'value' => '',
			'items' => ($bVatMode) ? CCrmViewHelper::prepareSelectItemsForJS(CCrmVat::GetVatRatesListItems()) : null,
			'skip' => ($bVatMode) ? 'N' : 'Y'),
		array('textCode' => 'VAT_INCLUDED', 'type' => 'checkbox', 'value' => 'N', 'skip' => ($bVatMode) ? 'N' : 'Y'),
		array('textCode' => 'SECTION_ID', 'type' => 'select', 'value' => '0',
			'items' => CCrmViewHelper::prepareSelectItemsForJS(
					CCrmProductHelper::PrepareSectionListItems(CCrmCatalog::EnsureDefaultExists())
				), 'skip' => 'N'),
		array('textCode' => 'SORT', 'type' => 'text', 'maxLength' => 11, 'value' => 100, 'skip' => 'N')
	),
	"ownerCurrencyId" => $currencyID
);
unset($bVatMode);

$this->IncludeComponentTemplate();
