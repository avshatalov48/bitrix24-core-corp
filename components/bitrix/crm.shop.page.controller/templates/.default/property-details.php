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

$APPLICATION->IncludeComponent(
	"bitrix:crm.admin.page.controller",
	"",
	$arResult['CRM_ADMIN_PAGE_CONTROLLER_PARAMS'] + [
		'IS_ONLY_MENU' => true,
	]
);

$propertyId = (int)$arResult['PROPERTY_ID'];
$iblockId = (int)$arResult['IBLOCK_ID'];

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:iblock.property.details',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'ID' => $propertyId,
			'IBLOCK_ID' => $iblockId,
			'DETAIL_PAGE_URL' => "/shop/settings/menu_catalog_attributes_{$iblockId}/details/#ID#/",
			'LIST_VALUES_URL' => "/shop/settings/menu_catalog_attributes_{$iblockId}/details/{$propertyId}/list-values/",
			'DIRECTORY_ITEMS_URL' => "/shop/settings/menu_catalog_attributes_{$iblockId}/details/{$propertyId}/directory-items/",
		],
		'RELOAD_GRID_AFTER_SAVE' => false,
		'CLOSE_AFTER_SAVE' => false,
		'USE_UI_TOOLBAR' => 'Y',
		'USE_PADDING' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => "/shop/settings/menu_catalog_attributes_{$iblockId}/",
	]
);

?>
<script>
	(function() {
		BX.SidePanel.Instance.bindAnchors({
			rules: [
				{
					condition: [
						'/shop/settings/menu_catalog_attributes_(\\d+)/details/(\\d+)/',
					],
					options: {
						width: 900,
						cacheable: false,
						allowChangeHistory: true,
					}
				},
			],
		});
	})();
</script>
