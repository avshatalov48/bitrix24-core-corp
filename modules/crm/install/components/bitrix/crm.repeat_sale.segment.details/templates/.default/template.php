<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

Loc::loadMessages(__FILE__);
Loc::loadLanguageFile(__FILE__);

Extension::load([
	'crm.repeat-sale.segment',
]);

/** @var $arResult array */
$data = $arResult['data'] ?? [];
$config = [
	'readOnly' => $arResult['readOnly'] ?? true,
];
$analytics = [
	'section' => $arResult['analytics']['section'],
];
?>
<script>
	BX.ready(() => {
		new BX.Crm.RepeatSale.Segment(
			'repeatSaleSegmentDetails',
			{
				data: <?= Json::encode($data) ?>,
				config: <?= Json::encode($config) ?>,
				events: {
					onSave: () => {
						if (top.BX.Main?.gridManager)
						{
							const grid = top.BX.Main.gridManager.getInstanceById('crm_repeat_sale_segment_grid');
							if (grid)
							{
								grid.reload();
							}
						}
					}
				},
				analytics: <?= Json::encode($analytics) ?>,
			},
		);
	});
</script>
<div id="repeatSaleSegmentDetails" class="crm-repeat-sale-segment-details"></div>
