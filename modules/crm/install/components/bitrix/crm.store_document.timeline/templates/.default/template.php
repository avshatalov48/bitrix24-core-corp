<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

global $APPLICATION;
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');
?>
<script>
	BX.ready(
		function()
		{
			BX.message({
				'CRM_TIMELINE_HISTORY_STUB': '<?= \Bitrix\Main\Localization\Loc::getMessage('TIMELINE_STUB_MESSAGE') ?>',
			});

			BX.addCustomEvent('Schedule:onBeforeRefreshLayout', function(event) {
				var plannedBlock = document.querySelector('.crm-entity-stream-section.crm-entity-stream-section-planned');
				if (plannedBlock)
				{
					BX.hide(plannedBlock.parentElement);
				}
			});
		}
	);
</script>
<?php
$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	$arResult['ACTIVITY_EDITOR_PARAMS'],
	$component,
	array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.timeline',
	'',
	$arResult['TIMELINE_PARAMS'],
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>
