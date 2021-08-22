<?php

use Bitrix\Catalog\v2\Integration\Landing\ProductTokenizer;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}

/**
 * @var StoreCatalogListBlockV3 $classBlock
 */

$sectionId = $classBlock->get('SECTION_ID');
$sectionCode = $classBlock->get('SECTION_CODE');
$sectionUrl = $classBlock->get('SECTION_URL');
$detailUrl = $classBlock->get('DETAIL_URL');

// for replace in public mode
if ($sectionUrl) {
	$sectionUrl = '#system_catalog#SECTION_CODE_PATH#/';
}
if ($detailUrl) {
	$detailUrl = '#system_catalogitem/#ELEMENT_CODE#/';
}
?>

<? $APPLICATION->IncludeComponent(
	'bitrix:landing.blocks.cmpfilter',
	'',
	[
		'FILTER' => [],
		'FILTER_NAME' => 'arrFilter',
	]
);

if (
	Loader::includeModule('catalog')
	&& method_exists(ProductTokenizer::class, 'decodeFromRequest')
)
{
	$productIds = ProductTokenizer::decodeFromRequest(
		Bitrix\Main\Context::getCurrent()->getRequest()
	);
}
else
{
	$productIds = null;
}
?>

<section class="landing-block g-pt-20 g-pb-20">
	<div class="landing-component">
		<? $APPLICATION->IncludeComponent(
			'bitrix:catalog.section',
			'store_v3',
			[
				'IBLOCK_TYPE' => '',
				'IBLOCK_ID' => $classBlock->get('IBLOCK_ID'),
				'SECTION_ID' => $sectionId,
				'SECTION_CODE' => $sectionCode,
				'SECTION_USER_FIELDS' => [],
				'EXTERNAL_PRODUCT_IDS' => $productIds,
				'ELEMENT_SORT_FIELD' => 'sort',
				'ELEMENT_SORT_ORDER' => 'desc',
				'ELEMENT_SORT_FIELD2' => '',
				'ELEMENT_SORT_ORDER2' => '',
				'FILTER_NAME' => 'arrFilter',
				'INCLUDE_SUBSECTIONS' => 'Y',
				'SHOW_ALL_WO_SECTION' => 'Y',
				'PAGE_ELEMENT_COUNT' => '6',
				'LINE_ELEMENT_COUNT' => '1',
				'PROPERTY_CODE' => [
					0 => 'ARTNUMBER',
					1 => 'MANUFACTURER',
					2 => 'MATERIAL',
				],
				'OFFERS_FIELD_CODE' => [
					0 => 'NAME'
				],
				'OFFERS_PROPERTY_CODE' => [
					0 => 'COLOR_REF',
					1 => 'SIZES_SHOES',
					2 => 'SIZES_CLOTHES',
				],
				'OFFERS_SORT_FIELD' => 'sort',
				'OFFERS_SORT_ORDER' => 'desc',
				'OFFERS_LIMIT' => '0',
				'TEMPLATE_THEME' => 'vendor',
				'PRODUCT_DISPLAY_MODE' => 'Y',
				'ADD_PICT_PROP' => 'MORE_PHOTO',
				'LABEL_PROP' => [
					0 => 'NEWPRODUCT',
					1 => 'SALELEADER',
					2 => 'SPECIALOFFER',
				],
				'OFFER_ADD_PICT_PROP' => 'MORE_PHOTO',
				'OFFER_TREE_PROPS' => [
					0 => 'COLOR_REF',
					1 => 'SIZES_SHOES',
					2 => 'SIZES_CLOTHES',
				],
				'MESS_BTN_BUY' => '',
				'MESS_BTN_ADD_TO_BASKET' => '',
				'MESS_BTN_SUBSCRIBE' => '',
				'MESS_BTN_DETAIL' => '',
				'MESS_NOT_AVAILABLE' => '',
				'SECTION_URL' => $sectionUrl,
				'DETAIL_URL' => $detailUrl,
				'HIDE_DETAIL_URL' => $classBlock->get('HIDE_DETAIL_URL'),
				'SECTION_ID_VARIABLE' => 'SECTION_CODE',
				'AJAX_MODE' => 'N',
				'AJAX_OPTION_JUMP' => 'Y',
				'AJAX_OPTION_STYLE' => 'Y',
				'AJAX_OPTION_HISTORY' => 'N',
				'CACHE_TYPE' => 'A',
				'CACHE_TIME' => '36000000',
				'CACHE_GROUPS' => 'Y',
				'SET_META_KEYWORDS' => 'Y',
				'META_KEYWORDS' => '',
				'SET_META_DESCRIPTION' => 'Y',
				'META_DESCRIPTION' => '',
				'BROWSER_TITLE' => '-',
				'ADD_SECTIONS_CHAIN' => 'Y',
				'SET_TITLE' => $classBlock->get('SET_TITLE'),
				'ALLOW_SEO_DATA' => 'Y',
				'SET_STATUS_404' => 'N',
				'CACHE_FILTER' => 'N',
				'CONVERT_CURRENCY' => 'Y',
				'BASKET_URL' => '#system_order',
				'ACTION_VARIABLE' => $classBlock->get('ACTION_VARIABLE'),
				'ACTION_COMPARE_VARIABLE' => 'compare',
				'PRODUCT_ID_VARIABLE' => 'id',
				'PRODUCT_QUANTITY_VARIABLE' => 'quantity',
				'ADD_PROPERTIES_TO_BASKET' => 'N',
				'PRODUCT_PROPS_VARIABLE' => 'prop',
				'PARTIAL_PRODUCT_PROPERTIES' => 'Y',
				'PRODUCT_PROPERTIES' => [
					0 => 'BRAND_REF',
					1 => 'NEWPRODUCT',
					2 => 'SALELEADER',
					3 => 'SPECIALOFFER',
					4 => 'MATERIAL',
					5 => 'RECOMMEND',
				],
				'OFFERS_CART_PROPERTIES' => [
					0 => 'ARTNUMBER',
					1 => 'COLOR_REF',
					2 => 'SIZES_SHOES',
					3 => 'SIZES_CLOTHES',
				],
				'PAGER_TEMPLATE' => 'round',
				'DISPLAY_TOP_PAGER' => 'N',
				'DISPLAY_BOTTOM_PAGER' => 'N',
				'PAGER_TITLE' => '',
				'PAGER_SHOW_ALWAYS' => 'N',
				'PAGER_DESC_NUMBERING' => 'N',
				'PAGER_DESC_NUMBERING_CACHE_TIME' => '36000',
				'PAGER_SHOW_ALL' => 'N',
				'AJAX_OPTION_ADDITIONAL' => '',
				'SET_BROWSER_TITLE' => 'Y',
				'SHOW_CLOSE_POPUP' => 'Y',
				'MESS_BTN_COMPARE' => '',
				'ADD_TO_BASKET_ACTION' => 'BUY',
				'COMPONENT_TEMPLATE' => 'store_v3',
				'SEF_MODE' => 'N',
				'SET_LAST_MODIFIED' => 'N',
				'USE_MAIN_ELEMENT_SECTION' => 'N',
				'PAGER_BASE_LINK_ENABLE' => 'N',
				'SHOW_404' => 'N',
				'MESSAGE_404' => '',
				'PAGER_BASE_LINK' => '',
				'PAGER_PARAMS_NAME' => 'arrPager',
				'BACKGROUND_IMAGE' => 'UF_BACKGROUND_IMAGE',
				'DISABLE_INIT_JS_IN_COMPONENT' => 'N',
				'CUSTOM_FILTER' => '',
				'SHOW_SLIDER' => 'Y',
				'LABEL_PROP_MOBILE' => [
					0 => 'NEWPRODUCT',
					1 => 'SALELEADER',
					2 => 'SPECIALOFFER',
				],
				'LABEL_PROP_POSITION' => 'top-left',
				'DISCOUNT_PERCENT_POSITION' => 'bottom-right',
				'RCM_TYPE' => 'personal',
				'RCM_PROD_ID' => '',
				'USE_OFFER_NAME' => 'Y',
				'LAZY_LOAD' => 'Y',
				'LOAD_ON_SCROLL' => $classBlock->get('EDIT_MODE') ? 'N' : 'Y',
				'DEFERRED_LOAD' => 'N',
				'CYCLIC_LOADING' => 'N',
				'CYCLIC_LOADING_COUNTER_NAME' => 'cycleCount',
				'SECTIONS_OFFSET_MODE' => 'F',
				'SECTIONS_SECTION_ID' => $classBlock->get('LANDING_SECTION_ID'),
				'PROPERTY_CODE_MOBILE' => [
					0 => 'ARTNUMBER',
					1 => 'MANUFACTURER',
					2 => 'MATERIAL',
				],
				'SLIDER_INTERVAL' => '3000',
				'SLIDER_PROGRESS' => 'Y',
				'MESS_BTN_LAZY_LOAD' => '',
				'SHOW_MAX_QUANTITY' => 'M',
				'SHOW_FROM_SECTION' => 'Y',
				'COMPATIBLE_MODE' => 'N',
				'COMPOSITE_FRAME_MODE' => 'A',
				'COMPOSITE_FRAME_TYPE' => 'AUTO',
				'COMPARE_NAME' => 'CATALOG_COMPARE_LIST',
				'MESS_SHOW_MAX_QUANTITY' => '',
				'RELATIVE_QUANTITY_FACTOR' => '5',
				'MESS_RELATIVE_QUANTITY_MANY' => '',
				'MESS_RELATIVE_QUANTITY_FEW' => '',
				'USE_COMPARE_LIST' => 'N',
				'STRICT_SECTION_CHECK' => 'N',
				'CHECK_LANDING_PRODUCT_SECTION' => 'Y',
				'PREDICT_ELEMENT_COUNT' => 'Y',
				'COMPARE_PATH' => '#system_compare',
				'HIDE_NOT_AVAILABLE' => $classBlock->get('HIDE_NOT_AVAILABLE'),
				'HIDE_NOT_AVAILABLE_OFFERS' => $classBlock->get('HIDE_NOT_AVAILABLE_OFFERS'),
				'PRODUCT_SUBSCRIPTION' => 'N',
				'USE_PRODUCT_QUANTITY' => $classBlock->get('USE_PRODUCT_QUANTITY'),
				'DISPLAY_COMPARE' => 'N',
				'PRICE_CODE' => $classBlock->get('PRICE_CODE'),
				'USE_PRICE_COUNT' => $classBlock->get('USE_PRICE_COUNT'),
				'SHOW_PRICE_COUNT' => $classBlock->get('SHOW_PRICE_COUNT'),
				'CURRENCY_ID' => $classBlock->get('CURRENCY_ID'),
				'PRICE_VAT_INCLUDE' => $classBlock->get('PRICE_VAT_INCLUDE'),
				'SHOW_OLD_PRICE' => $classBlock->get('SHOW_OLD_PRICE'),
				'SHOW_DISCOUNT_PERCENT' => $classBlock->get('SHOW_DISCOUNT_PERCENT'),
				'USE_ENHANCED_ECOMMERCE' => $classBlock->get('USE_ENHANCED_ECOMMERCE'),
				'DATA_LAYER_NAME' => $classBlock->get('DATA_LAYER_NAME'),
				'BRAND_PROPERTY' => $classBlock->get('BRAND_PROPERTY'),
				'CUSTOM_SITE_ID' => $classBlock->get('SITE_ID'),
				'CONTEXT_SITE_ID' => $classBlock->get('SITE_ID'),
				'SECTIONS_CHAIN_START_FROM' => 1,
			],
			false
		); ?>
	</div>
</section>