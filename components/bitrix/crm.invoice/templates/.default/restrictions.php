<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
?>
<script>
	BX.ready(function() {
		<?=\Bitrix\Crm\Restriction\RestrictionManager::getInvoicesRestriction()->prepareInfoHelperScript();?>;
		var slider = top.BX && top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);
		if(slider)
		{
			slider.close();
		}
		else
		{
			BX.addCustomEvent("SidePanel.Slider:onCloseComplete", function(event) {
				location.href = "/crm/deal/";
			});
		}
	});
</script>

<?php
\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID'   => 'LEAD_RESTRICTED',
		'HEADERS'   => [
			['id' => 'ID', 'name' => 'ID'],
		],
		'ROWS'      => [],
		'STUB'      => [
			'title' => \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_RESTRICTION_GRID_TITLE'),
			'description' => \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_RESTRICTION_GRID_TEXT'),
		],
	],
	$component,
	[
		'HIDE_ICONS' => 'Y',
	]
);