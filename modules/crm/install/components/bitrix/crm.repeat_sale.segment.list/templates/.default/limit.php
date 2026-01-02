<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;

/** @var object $component */

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

if (!Container::getInstance()->getRepeatSaleAvailabilityChecker()->isItemsCountsLessThenLimit()):
	?>
	<script>
	BX.ready(() => {
		top.BX.UI?.InfoHelper?.show('limit_v2_crm_repeat_sale_max');

		const slider = top?.BX?.SidePanel?.Instance.getSliderByWindow(window);
		if (slider)
		{
			slider.close();
		}
		else
		{
			BX.addCustomEvent('SidePanel.Slider:onCloseComplete', () => {
				location.href = '/crm/deal/';
			});
		}
	});
</script>
<?php
endif;

Container::getInstance()->getLocalization()->loadMessages();

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => 'REPEAT_SALE_RESTRICTED',
		'HEADERS' => [
			[
				'id' => 'ID',
				'name' => 'ID',
			],
		],
		'ROWS' => [],
		'STUB' => [
			'title' => Loc::getMessage('CRM_FEATURE_RESTRICTION_GRID_TITLE'),
			'description' => Loc::getMessage('CRM_FEATURE_RESTRICTION_GRID_TEXT'),
		],
	],
	$component,
	[
		'HIDE_ICONS' => 'Y',
	],
);
