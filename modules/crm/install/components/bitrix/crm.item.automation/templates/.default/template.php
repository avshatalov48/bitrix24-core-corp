<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->SetTitle($arResult['PAGE_TITLE']);
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-background');
$this->getComponent()->addToolbar($this);

if ($arResult['CATEGORIES'] && count($arResult['CATEGORIES']) > 1)
{
	?><div class="crm-config-automation-button-container">
		<div class="ui-btn ui-btn-dropdown ui-btn-light-border" data-role="category-selector">
			<?=htmlspecialcharsbx($arResult['CATEGORY_NAME'])?>
		</div>
	</div><?php
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.automation',
	'',
	[
		'DOCUMENT_TYPE' => ['crm', $arResult['DOCUMENT_NAME'], $arResult["DOCUMENT_TYPE"]],
		'DOCUMENT_ID' => $arResult['DOCUMENT_ID'],
		'TITLE_VIEW' => $arResult['TITLE_VIEW'],
		'TITLE_EDIT' => $arResult['TITLE_EDIT'],
		'ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
		'ENTITY_CATEGORY_ID' => $arResult['ENTITY_CATEGORY_ID']
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
