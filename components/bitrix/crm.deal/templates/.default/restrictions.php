<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;

/** @var object $component */

use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

if (RestrictionManager::getActivityFieldRestriction()->isExceeded()):?>
	<script>
		BX.ready(function() {
			<?= RestrictionManager::getActivityFieldRestriction()->prepareInfoHelperScript() ?>;

			let slider = top.BX && top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);
			if (slider)
			{
				slider.close();
			}
			else
			{
				BX.addCustomEvent("SidePanel.Slider:onCloseComplete", function() {
					location.href = "/crm/deal/";
				});
			}
		})
	</script>
<?endif;

Container::getInstance()->getLocalization()->loadMessages();

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => 'DEAL_RESTRICTED',
		'HEADERS' => [
			[
				'id' => 'ID',
				'name' => 'ID'
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
	]
);
