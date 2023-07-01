<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'ui.alerts',
	'ui.tooltip',
	'ui.icons',
	'ui.notification',
	'crm.store-document-grid-manager',
]);

global $APPLICATION;

$title = Loc::getMessage('CRM_DOCUMENT_LIST_TITLE_' . mb_strtoupper($arResult['MODE']));
$APPLICATION->SetTitle($title);

$this->setViewTarget('above_pagetitle');
$APPLICATION->IncludeComponent(
	'bitrix:catalog.store.document.control_panel',
	'',
	[
		'PATH_TO' => $arResult['PATH_TO'],
	]
);
$this->endViewTarget();

if (!empty($arResult['ERROR_MESSAGES']) && is_array($arResult['ERROR_MESSAGES']))
{
	if (count($arResult['ERROR_MESSAGES']) === 1 && is_array($arResult['ERROR_MESSAGES'][0]))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:ui.info.error',
			'',
			$arResult['ERROR_MESSAGES'][0]
		);
	}
	else
	{
		foreach ($arResult['ERROR_MESSAGES'] as $error)
		{

			?>
			<div class="ui-alert ui-alert-danger catalog-store-document-list--alert" style="margin-bottom: 0;">
				<span class="ui-alert-message"><?= $error ?></span>
			</div>
			<?php
		}
	}

	return;
}

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$arResult['GRID']
);

if ($arResult['OPEN_INVENTORY_MANAGEMENT_SLIDER'])
{
	?>
	<script>
		var currentSlider = BX.SidePanel.Instance.getTopSlider();
		if (!currentSlider || !currentSlider.data.get('preventMasterSlider'))
		{
			BX.SidePanel.Instance.open(
				"<?= $arResult['MASTER_SLIDER_URL'] ?>",
				{
					cacheable: false,
					data: {
						openGridOnDone: false,
					},
					events: {
						onCloseComplete: function(event) {
							let slider = event.getSlider();
							if (!slider)
							{
								return;
							}

							if (slider.getData().get('isInventoryManagementEnabled'))
							{
								document.location.reload();
							}
						}
					}
				}
			);
		}
	</script>
	<?php
}
?>

<script>
	BX.ready(function() {
		BX.Crm.StoreDocumentGridManager.Instance = new BX.Crm.StoreDocumentGridManager({
			gridId: '<?= $arResult['GRID']['GRID_ID'] ?>',
			filterId: '<?= $arResult['FILTER_ID'] ?>',
			isConductDisabled: <?= $arResult['OPEN_INVENTORY_MANAGEMENT_SLIDER_ON_ACTION'] ? 'true' : 'false' ?>,
			masterSliderUrl: <?= CUtil::PhpToJSObject($arResult['MASTER_SLIDER_URL']) ?>,
			inventoryManagementSource: <?= CUtil::PhpToJSObject($arResult['INVENTORY_MANAGEMENT_SOURCE']) ?>,
			isInventoryManagementDisabled: <?= CUtil::PhpToJSObject($arResult['IS_INVENTORY_MANAGEMENT_DISABLED']) ?>,
			inventoryManagementFeatureCode: <?= CUtil::PhpToJSObject($arResult['INVENTORY_MANAGEMENT_FEATURE_SLIDER_CODE']) ?>,
		});
	});
</script>
