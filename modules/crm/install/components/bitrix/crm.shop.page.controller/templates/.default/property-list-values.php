<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 */

$iblockId = (int)$arResult['IBLOCK_ID'];
$propertyId = (int)$arResult['PROPERTY_ID'];

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:iblock.property.type.list.values',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'PROPERTY_ID' => $propertyId,
			'IBLOCK_ID' => $iblockId,
		],
		'RELOAD_GRID_AFTER_SAVE' => false,
		'CLOSE_AFTER_SAVE' => false,
		'USE_UI_TOOLBAR' => 'Y',
		'USE_PADDING' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => "/shop/settings/menu_catalog_attributes_{$iblockId}/details/{$propertyId}/",
	]
);
