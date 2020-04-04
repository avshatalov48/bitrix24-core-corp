<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

// Product properties
foreach($arResult['PROPS'] as $propID => $arProp)
{
	if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
		&& !array_key_exists($arProp['USER_TYPE'], $arResult['PROP_USER_TYPES'])
	)
		continue;

	if (isset($arResult['PROPERTY_VALUES'][$propID]))
	{
		$arResult['FIELDS']['tab_1'][] = array(
			'id' => ($arProp['PROPERTY_TYPE'] === 'L' && $arProp['MULTIPLE'] === 'Y' && empty($arProp['USER_TYPE'])) ? $propID.'[]' : $propID,
			'name' => $arProp['NAME'],
			'type' => 'custom',
			'value' => $arResult['PROPERTY_VALUES'][$propID],
			'isTactile' => true
		);
	}
}

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1'],
	'display' => false
);

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);

$arResult['CRM_CUSTOM_PAGE_TITLE'] =
	$arResult['PRODUCT_ID'] > 0
		? GetMessage('CRM_PRODUCT_NAV_TITLE_EDIT', array('#NAME#' => $arResult['PRODUCT']['NAME']))
		: '';

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'show',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'GRID_ID' => $arResult['GRID_ID'],
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		'TACTILE_FORM_ID' => 'CRM_PRODUCT_EDIT',
		'TABS' => $arTabs,
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y',
		'SHOW_FORM_TAG' => 'N',
		'SHOW_TABS' => 'N'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>