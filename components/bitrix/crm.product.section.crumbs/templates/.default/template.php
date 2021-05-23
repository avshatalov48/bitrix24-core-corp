<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var \CCrmProductSectionCrumbsComponent $component */

$containerId = 'crm-product-section-crumbs-'.$component->getComponentId();
$childrenCrumbs = array();
?>
<div id="<?= $containerId ?>" class="bx-crm-interface-product-section-crumbs ovh"></div>
<script type="text/javascript">
	BX.namespace("BX.Crm");
	BX.Crm["ProductSectionCrumbs_<?= $component->getComponentId() ?>"] = new BX.Crm.ProductSectionCrumbsClass({
		componentId: "<?= $component->getComponentId() ?>",
		containerId: "<?= $containerId ?>",
		catalogId: "<?= CUtil::JSEscape($arResult['CATALOG_ID']) ?>",
		sectionId: "<?= CUtil::JSEscape($arResult['SECTION_ID']) ?>",
		crumbs: <?= CUtil::PhpToJSObject($arResult['CRUMBS']) ?>,
		showOnlyDeleted: <?= (int)$arResult['SHOW_ONLY_DELETED'] ?>,
		jsEventsMode: <?= CUtil::JSEscape($arResult['JS_EVENTS_MODE'] === 'Y' ? 'true' : 'false') ?>,
		jsEventsManagerId: "<?= CUtil::JSEscape($arResult['JS_EVENTS_MANAGER_ID']) ?>"
	});
</script>