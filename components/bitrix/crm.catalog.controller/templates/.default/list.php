<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/** @var \CBitrixComponentTemplate $this  */
/** @var \CrmCatalogControllerComponent $component */

$component->showCrmControlPanel();

$APPLICATION->IncludeComponent(
	'bitrix:catalog.product.grid',
	'',
	[
		'GRID_ID' => 'CrmProductGrid',
		'IBLOCK_ID' => $arResult['IBLOCK_ID'],
		'SECTION_ID' => $arResult['VARIABLES']['SECTION_ID'] ?? null,
		'URL_BUILDER' => $arResult['URL_BUILDER'],
		'USE_NEW_CARD' => $arResult['USE_NEW_CARD'],
		'LIST_MODE' => $arResult['URL_BUILDER']->getListMode(),
	],
	$component,
	['HIDE_ICONS' => 'Y']
);

?>
<script>
BX.ready(function() {
	new BX.Crm.Catalog({
		gridId: 'CrmProductGrid',
	});
});
</script>
