<?php

use Bitrix\Main;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $propertyKey => $property):?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?= htmlspecialcharsbx($property['Name']) ?>:
		</span>
		<?= $dialog->renderFieldControl($property, null, !empty($property['AllowSelection'])) ?>
	</div>
<?endforeach;

//TODO: do nice
if (Main\Loader::includeModule('iblock') && Main\Loader::includeModule('catalog')):
?>
<script>
	BX.ready(function()
	{
		var node = document.querySelector('[name="product_id"]');

		var dialog = new BX.UI.EntitySelector.Dialog({
			context: 'catalog-products',
			entities: [
				{
					id: 'product',
					options: {
						iblockId: <?= (int)\Bitrix\Crm\Product\Catalog::getDefaultId() ?>,
						basePriceId: <?= (int)\Bitrix\Crm\Product\Price::getBaseId() ?>
					}
				}
			],
			targetNode: node,
			height: 300,
			multiple: false,
			dropdownMode: true,
			enableSearch: true,
			events: {
				'Item:onBeforeSelect': function(event)
				{
					event.preventDefault();
					node.value = event.getData().item.getId();
				}
			}
		});

		BX.bind(node, 'click', function() {
			dialog.show();
		});
	});
</script>
<?php
endif;
