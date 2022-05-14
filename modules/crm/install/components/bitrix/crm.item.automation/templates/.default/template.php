<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->SetTitle($arResult['PAGE_TITLE']);
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-background');

$this->getComponent()->addToolbar($this);
$this->getComponent()->addTopPanel($this);

$this->setViewTarget("inside_pagetitle_below", 100); ?>
<div class="crm-item-automation-subtitle">
	<?= htmlspecialcharsbx($arResult['PAGE_SUBTITLE']) ?>
</div>
<? $this->endViewTarget();

$APPLICATION->IncludeComponent(
	'bitrix:crm.automation',
	'',
	[
		'TITLE_VIEW' => $arResult['TITLE_VIEW'],
		'TITLE_EDIT' => $arResult['TITLE_EDIT'],
		'ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
		'ENTITY_ID' => (int)$arParams['id'],
		'ENTITY_CATEGORY_ID' => $arResult['ENTITY_CATEGORY_ID'],
		'back_url' => $arResult['BACK_URL'],
		'CATEGORY_SELECTOR' => (
			$arResult['CATEGORY_NAME']
				? ['TEXT' => $arResult['CATEGORY_NAME']]
				: null
		),
	],
	$this
);
?>
<script>
BX.ready(function() {
	var categorySelector = document.querySelector('[data-role="category-selector"]');
	if (categorySelector)
	{
		var items = <?=CUtil::PhpToJSObject($arResult['CATEGORIES'])?>;
		if (!BX.Type.isArray(items))
		{
			return;
		}
		var itemsLength = items.length;
		for (var i = 0; i < itemsLength; i++)
		{
			items[i].onclick = function(event, item) {
				window.location.href = item.link + '?IFRAME=Y';
			}
		}
		var menu = new BX.PopupMenuWindow({
			bindElement: categorySelector,
			items: items,
		});

		categorySelector.addEventListener("click", function() {
			menu.show();
		});
	}
});
</script>
