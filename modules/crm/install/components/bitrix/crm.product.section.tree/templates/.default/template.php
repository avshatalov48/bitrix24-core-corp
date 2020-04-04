<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var \CCrmProductSectionTreeComponent $component */

$rootTitle = GetMessage('CRM_PRODUCT_SECTION_TREE_TITLE');

?>
<div class="bx-crm-wf-section-name tal"><?php echo $rootTitle ?></div>
<div id="crm-product-section-tree-<?= $component->getComponentId() ?>" style="display: none;"></div>
<script type="text/javascript">
	BX.message({
		"CRM_JS_STATUS_ACTION_SUCCESS": "<?=CUtil::JSEscape(GetMessage('CRM_JS_STATUS_ACTION_SUCCESS'))?>",
		"CRM_JS_STATUS_ACTION_ERROR": "<?=CUtil::JSEscape(GetMessage('CRM_JS_STATUS_ACTION_ERROR'))?>",
		"CRM_PRODUCT_SECTION_TREE_TITLE": "<?=CUtil::JSEscape($rootTitle)?>"
	});
	BX.Crm['ProductSectionTree_<?= $component->getComponentId() ?>'] = new BX.Crm.ProductSectionTreeClass({
		catalogId: <?= CUtil::PhpToJSObject($arResult['CATALOG_ID']) ?>,
		sectionId: <?= CUtil::PhpToJSObject($arResult['SECTION_ID']) ?>,
		treeInfo: <?= CUtil::PhpToJSObject($arResult['INITIAL_TREE']) ?>,
		containerId: "crm-product-section-tree-<?= $component->getComponentId() ?>",
		productListUri: "<?= CUtil::JSEscape($arResult['PAGE_URI_TEMPLATE']) ?>",
		jsEventsMode: <?= CUtil::JSEscape($arResult['JS_EVENTS_MODE'] === 'Y' ? 'true' : 'false') ?>,
		jsEventsManagerId: "<?= CUtil::JSEscape($arResult['JS_EVENTS_MANAGER_ID']) ?>"
	});
</script>
