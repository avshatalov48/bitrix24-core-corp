<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

$APPLICATION->SetTitle($arResult['PAGE_TITLE'] ?? '');
$APPLICATION->SetPageProperty('BodyClass', (isset($bodyClass) ? $bodyClass . ' ' : '') . 'no-background');

$this->getComponent()->addToolbar($this);
$this->getComponent()->addTopPanel($this);

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

$this->setViewTarget("inside_pagetitle_below", 100); ?>
<div class="crm-item-automation-subtitle">
	<?= htmlspecialcharsbx($arResult['PAGE_SUBTITLE']) ?>
</div>
<?php $this->endViewTarget();

$APPLICATION->IncludeComponent(
	'bitrix:crm.automation',
	'',
	[
		'TITLE_VIEW' => $arResult['TITLE_VIEW'] ?? '',
		'TITLE_EDIT' => $arResult['TITLE_EDIT'] ?? '',
		'ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
		'ENTITY_ID' => (int)($arParams['id'] ?? 0),
		'ENTITY_CATEGORY_ID' => $arResult['ENTITY_CATEGORY_ID'] ?? null,
		'back_url' => $arResult['BACK_URL'] ?? '',
		'CATEGORY_SELECTOR' => empty($arResult['CATEGORY_NAME']) ? null : ['TEXT' => $arResult['CATEGORY_NAME']],
	],
	$this
);
?>
<script>
BX.ready(function() {
	var categorySelector = document.querySelector('[data-role="category-selector"]');
	if (categorySelector)
	{
		var items = <?=CUtil::PhpToJSObject($arResult['CATEGORIES'] ?? [])?>;
		if (!BX.Type.isArray(items))
		{
			return;
		}
		var itemsLength = items.length;
		for (var i = 0; i < itemsLength; i++)
		{
			items[i].onclick = function(event, item) {
				const itemId = (new BX.Uri(window.location.href)).getQueryParam('id');
				const url = new BX.Uri(item.link);
				if (itemId)
				{
					url.setQueryParam('id', BX.Text.toInteger(itemId));
				}
				url.setQueryParam('IFRAME', 'Y');

				window.location.href = url.toString();
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
