<?php

use Bitrix\Main;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field): ?>
	<tr>
		<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
		<td width="60%">
			<?= $dialog->renderFieldControl($field, null, !empty($field['AllowSelection']), 0) ?>
		</td>
	</tr>
<?endforeach;

//TODO: do nice
if (Main\Loader::includeModule('iblock') && Main\Loader::includeModule('catalog')):
	?>
	<script>
		BX.ready(function()
		{
			BX.loadExt('ui.entity-selector').then(function()
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
		});
	</script>
<?php
endif;
