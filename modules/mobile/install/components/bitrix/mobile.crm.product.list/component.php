<?php

use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Main\Loader;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!Loader::includeModule('catalog'))
{
	ShowError('The Commercial Catalog module is not installed.');
	return;
}

global $APPLICATION;

if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult["IS_CREATE_PERMITTED"] = AccessController::getCurrent()->check(ActionDictionary::ACTION_PRODUCT_ADD);

$activeSectionID = $arParams['SECTION_ID'] = isset($arParams['SECTION_ID']) ? intval($arParams['SECTION_ID']) : 0;
if(isset($_GET['SECTION_ID']))
{
	$activeSectionID = intval($_GET['SECTION_ID']);
}

$currencyID = $arParams['CURRENCY_ID'] = isset($arParams['CURRENCY_ID']) ? $arParams['CURRENCY_ID'] : '';
if(isset($_GET['currency_id']))
{
	$currencyID = $_GET['currency_id'];
}
$arResult['CURRENCY_ID'] = $currencyID;

$listMode = $arParams['LIST_MODE'] = isset($arParams['LIST_MODE'])? mb_strtoupper($arParams['LIST_MODE']) : '';
if(isset($_GET['list_mode']))
{
	$listMode = mb_strtoupper($_GET['list_mode']);
}
$arResult['LIST_MODE'] = $listMode;

$arResult["AJAX_PATH"] = '/mobile/?mobile_action=mobile_crm_product_actions';

if(!isset($arParams['GRID_ID']) || $arParams['GRID_ID'] === '')
{
	$arParams['GRID_ID'] = 'mobile_crm_product_list';
}

$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $arParams["GRID_ID"]);

//sort
$sort = array('NAME' => 'ASC');
if (isset($gridOptions["sort_by"]) && isset($gridOptions["sort_order"]))
	$sort = array($gridOptions["sort_by"] => $gridOptions["sort_order"]);

//select
$commonSelect = array(
	'NAME', 'FORMATTED_PRICE', 'SECTION_ID', 'DETAIL_PICTURE', 'MEASURE'
);

if (isset($gridOptions["fields"]) && is_array($gridOptions["fields"]))
	$commonSelect = $gridOptions["fields"];

$select = $commonSelect;

if (!in_array("ID", $select))
{
	$select[] = "ID";
}

if (!in_array("NAME", $select))
{
	$select[] = "NAME";
}

if (in_array("FORMATTED_PRICE", $select))
	$select = array_merge($select, array('PRICE', 'CURRENCY_ID'));

if ($arResult['LIST_MODE'] == 'SELECTOR')
{
	if (!in_array("PRICE", $select))
	{
		$select[] = "PRICE";
	}

	if (!in_array("CURRENCY_ID", $select))
	{
		$select[] = "CURRENCY_ID";
	}

	if (!in_array("MEASURE", $select))
	{
		$select[] = "MEASURE";
	}

	if (!isset($arParams["PAGEID_PRODUCT_SELECTOR_BACK"]))
		$arParams["PAGEID_PRODUCT_SELECTOR_BACK"] = "";
}

//filter
$catalogID = isset($arParams['~CATALOG_ID']) ? intval($arParams['~CATALOG_ID']) : 0;
if ($catalogID <= 0)
{
	$catalogID = CCrmCatalog::EnsureDefaultExists();
}

$filter = array('CATALOG_ID' => $catalogID);

if(isset($_REQUEST["search"]) && !empty($_REQUEST["search"]))
{
	CUtil::JSPostUnescape();
	$filter['%NAME'] = trim($_REQUEST["search"]);
	$filter['LOGIC'] = 'OR';
}
else
{
	if($activeSectionID > 0)
	{
		$filter['SECTION_ID'] = $activeSectionID;
	}
	else
	{
		$filter['SECTION_ID'] = 0;
	}
}

$arResult['FILTER_PRESETS'] = array(
	'all' => array('name' => GetMessage('M_CRM_PRODUCT_LIST_FILTER_NONE'), 'fields' => array()),
	'filter_user' => array('name' => GetMessage('M_CRM_PRODUCT_LIST_PRESET_USER'), 'fields' => array())
);

if (isset($gridOptions['filters']['filter_user']))
{
	foreach($gridOptions['filters']['filter_user']['fields'] as $field => $value)
	{
		if ($value !== "")
			$arResult['FILTER_PRESETS']['filter_user']['fields'][$field] = $value;
	}
}

$arResult["CURRENT_FILTER"] = "all";
if (isset($gridOptions["currentFilter"]) && in_array($gridOptions["currentFilter"], array_keys($arResult['FILTER_PRESETS'])))
{
	$filter = array_merge($filter, $arResult['FILTER_PRESETS'][$gridOptions["currentFilter"]]['fields']);
	$arResult["CURRENT_FILTER"] = $gridOptions["currentFilter"];

	if(isset($filter['NAME']))
	{
		$filter['%NAME'] = $filter['NAME'];
		unset($filter['NAME']);
	}
	if (!(intval($activeSectionID) > 0) && isset($filter["SECTION_ID"]))
		$activeSectionID = $filter["SECTION_ID"];
}

if(!isset($_REQUEST["search"]))
{
	$arResult['SECTION_ID'] = $activeSectionID;
	$catalogID = isset($arParams['CATALOG_ID']) ? intval($arParams['CATALOG_ID']) : 0;
	if ($catalogID <= 0)
	{
		$catalogID = CCrmCatalog::EnsureDefaultExists();
	}
	$arResult['CATALOG_ID'] = $catalogID;

	// SECTIONS -->
	CModule::IncludeModule('iblock');
	$dbSections = CIBlockSection::GetList(
		array('left_margin' => 'asc'),
		array(
			'IBLOCK_ID' => $catalogID,
			//'SECTION_ID' => $activeSectionID,
			'GLOBAL_ACTIVE' => 'Y',
			'CHECK_PERMISSIONS' => 'N'
		),
		false,
		array('ID', 'NAME'),
		false
	);

	$arResult['ALL_SECTIONS'] = array();
	while ($section = $dbSections->GetNext())
	{
		$sectionID = $section['ID'] = intval($section['ID']);
		$arResult['ALL_SECTIONS'][$sectionID] = &$section;
		unset($section);
	}

	$dbSections = CIBlockSection::GetList(
		array('left_margin' => 'asc'),
		array(
			'IBLOCK_ID' => $catalogID,
			'SECTION_ID' => $activeSectionID,
			'GLOBAL_ACTIVE' => 'Y',
			'CHECK_PERMISSIONS' => 'N'
		),
		false,
		array('ID', 'NAME'),
		false
	);
	$arSections = array();
	while ($section = $dbSections->GetNext())
	{
		$sectionID = $section['ID'] = intval($section['ID']);
		$arSections[$sectionID] = &$section;
		unset($section);
	}

	if ($activeSectionID > 0 && isset($arResult['ALL_SECTIONS'][$activeSectionID]))
	{
		$arResult['ACTIVE_SECTION'] = $arResult['ALL_SECTIONS'][$activeSectionID];

		$arResult['ITEMS'][] = array(
			"VALUE" => $arResult['ACTIVE_SECTION']["NAME"],
			"TYPE" => "HR"
		);
	}

	$arResult['PRODUCT_SECTION_URL_TEMPLATE'] = $APPLICATION->GetCurPageParam(
		"AJAX_CALL=Y&FORMAT=json&SECTION_ID=#section_id#",
		array('AJAX_CALL', 'FORMAT', 'SECTION_ID', 'SEARCH', 'PAGING', $arResult['PAGER_PARAM'])
	);

	$arResult['SECTION_URL_TEMPLATE'] = $APPLICATION->GetCurPageParam(
		"SECTION_ID=#section_id#",
		array('AJAX_CALL', 'FORMAT', 'SECTION_ID', 'SEARCH', 'PAGING', $arResult['PAGER_PARAM'])
	);

	$productSectionParams = array(
		'PRODUCT_SECTION_URL_TEMPLATE' => $arResult['PRODUCT_SECTION_URL_TEMPLATE'],
		'SECTION_URL_TEMPLATE' => $arResult['SECTION_URL_TEMPLATE']
	);
	foreach ($arSections as $sectionID => &$section)
	{
		CCrmMobileHelper::PrepareProductSectionItem($section, $productSectionParams);

		$arResult['SECTIONS'][] = array(
			"TITLE" => $section["NAME"],
			"FIELDS" => $section,
			"ONCLICK" => "BX.Mobile.Crm.loadPageBlank('" . $section["SECTION_URL"] . "');",
			"DATA_ID" => "mobile-grid-item-" . $section["ID"]
		);
	}
	unset($section);
	//<-- SECTIONS
}

//fields to show
$arResult["FIELDS"] = array();
$allFields = CCrmMobileHelper::getProductFields();

foreach($commonSelect as $code)
{
	if ($code == "PREVIEW_PICTURE" || $code == "DETAIL_PICTURE")
		continue;

	$arResult["FIELDS"][$code] = $allFields[$code];
}

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

//navigation
$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => true,
	'bShowAll' => false
);
$navigation = CDBResult::GetNavParams($navParams);
$CGridOptions = new CGridOptions($arParams["GRID_ID"]);
$navParams = $CGridOptions->GetNavParams($navParams);

$measures = \Bitrix\Crm\Measure::getMeasures(100);
if (is_array($measures))
{
	foreach ($measures as $measure)
		$arResult['MEASURE_LIST_ITEMS'][$measure['ID']] = $measure;
}

$arResult['PRODUCTS'] = array();
$arPricesSelect = $arVatsSelect = array();

$select = CCrmProduct::DistributeProductSelect($select, $arPricesSelect, $arVatsSelect);

$dbRes = CCrmProduct::GetList($sort, $filter, $select, $navParams);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult["NAV_PARAM"] = array(
	'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
	'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
	'PAGE_NAVNUM' => intval($dbRes->NavNum),
	'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
);

$productParams = array(
	'CURRENCY_ID' => $currencyID,
	'SECTIONS' => &$arResult['ALL_SECTIONS'],
	'MEASURES' => $arResult['MEASURE_LIST_ITEMS']
);

$arProducts = $arProductId = array();
while ($product = $dbRes->GetNext())
{
	foreach ($arPricesSelect as $fieldName)
		$product['~'.$fieldName] = $product[$fieldName] = null;
	foreach ($arVatsSelect as $fieldName)
		$product['~'.$fieldName] = $product[$fieldName] = null;
	$arProductId[] = $product['ID'];
	$arProducts[$product['ID']] = $product;
}

CCrmProduct::ObtainPricesVats($arProducts, $arProductId, $arPricesSelect, $arVatsSelect);

$productMeasureInfos = \Bitrix\Crm\Measure::getProductMeasures($arProductId);
if (!is_array($productMeasureInfos))
	$productMeasureInfos = array();

unset($arProductId, $arPricesSelect, $arVatsSelect);

foreach ($arProducts as &$item)
{
	CCrmMobileHelper::PrepareProductItem($item, $productParams);
	$arResult['PRODUCTS'][] = $item;

	$isEditPermitted = AccessController::getCurrent()->check(ActionDictionary::ACTION_PRODUCT_EDIT);
	$isDeletePermitted = AccessController::getCurrent()->check(ActionDictionary::ACTION_PRODUCT_DELETE);

	$detailEditUrl = CComponentEngine::MakePathFromTemplate($arParams['PRODUCT_EDIT_URL_TEMPLATE'],
		array('product_id' => $item['ID'])
	);

	$arActions = array();
	if ($isEditPermitted)
	{
		$arActions[] = array(
			'TEXT' => GetMessageJS("M_CRM_PRODUCT_LIST_EDIT"),
			'ONCLICK' => "BXMobileApp.PageManager.loadPageModal({
							url: '".CUtil::JSEscape($detailEditUrl)."'
						});",
			'DISABLE' => false
		);
	}
	if ($isDeletePermitted)
	{
		$arActions[] = array(
			'TEXT' => GetMessageJS("M_CRM_PRODUCT_LIST_DELETE"),
			'ONCLICK' => "BX.Mobile.Crm.deleteItem('".$item["ID"]."', '".$arResult["AJAX_PATH"]."', 'list');",
			'DISABLE' => false
		);
	}

	$itemData = array(
		"ID" => 0,
		"PRODUCT_ID" => $item['ID'],
		"PRODUCT_NAME" => htmlspecialcharsbx($item['NAME']),
		"PRICE" => $item['PRICE'],
		"MEASURE" => $item['MEASURE'],
		"MEASURE_NAME" => $productMeasureInfos[$item['ID']][0]['SYMBOL'],
		"MEASURE_CODE" => $productMeasureInfos[$item['ID']][0]['CODE'],
		"MEASURE_ID" => $productMeasureInfos[$item['ID']][0]['ID'],
		"FORMATTED_PRICE" => $item['FORMATTED_PRICE'],
	);

	$arResult['ITEMS'][$item['ID']] = array(
		"TITLE" => $item["NAME"],
		"FIELDS" => $item,
		"ACTIONS" => $arActions,
		"ICON_HTML" => (!empty($item["PHOTO"])
			? '<span class="mobile-grid-field-title-logo"><img src="'.$item["PHOTO"].'" alt=""></span>'
			: '<span class="mobile-grid-field-title-icon"><img src="'.$this->getPath().'/images/icon-product.png" srcset="'.$this->getPath().'/images/icon-product.png 2x" alt=""></span>'
		),
		"ONCLICK" => $arResult['LIST_MODE'] == "SELECTOR"
			? 'BX.Mobile.Crm.ProductList.onSelectItem(\''.htmlspecialcharsbx(CUtil::JSEscape($arParams["ON_PRODUCT_SELECT_EVENT_NAME"])).'\', '.CUtil::PhpToJSObject($itemData).', \''.CUtil::JSEscape($arParams["PAGEID_PRODUCT_SELECTOR_BACK"]).'\')'
			: "BX.Mobile.Crm.loadPageBlank('/mobile/crm/product/?page=view&product_id=".$item["ID"]."');",
		"DATA_ID" => "mobile-grid-item-".$item["ID"]
	);
}
unset($arProducts);

$this->IncludeComponentTemplate();
