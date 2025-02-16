<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

$this->getComponent()->addTopPanel($this);
$this->getComponent()->addToolbar($this);

Extension::load([
	'crm.badge',
	'crm.router',
	'ui.icons',
	'ui.switcher',
	'ui.design-tokens',
]);

/** @var array $arResult */
global $APPLICATION;
$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_TITLE'));

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.interface.grid',
		'POPUP_COMPONENT_TEMPLATE_NAME' => 'titleflex',
		'POPUP_COMPONENT_PARAMS' => [
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['COLUMNS'],
			'SORT' => $arResult['SORT'],
			'ROWS' => $arResult['ROWS'],
			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',
			'PRESERVE_HISTORY' => true,
			'SHOW_NAVIGATION_PANEL' => true,
			'SHOW_PAGINATION' => true,
			'ALLOW_PIN_HEADER' => true,
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_PAGESIZE' => true,
			'ALLOW_COLUMNS_SORT' => false,
			'USE_UI_TOOLBAR' => 'Y',
			'NAV_PARAM_NAME' => $arResult['PAGE_NAVIGATION']->getId(),
			'CURRENT_PAGE' => $arResult['PAGE_NAVIGATION']->getCurrentPage(),
			'TOTAL_ROWS_COUNT' => $arResult['PAGE_NAVIGATION']->getRecordCount(),
			'NAV_OBJECT' => $arResult['PAGE_NAVIGATION'],
			'FILTER' => $arResult['FILTER'],
			'FILTER_PRESETS' => [],
			'FILTER_PARAMS' => [
				'CONFIG' => [
					'AUTOFOCUS' => false,
					'popupColumnsCount' => 3,
					'popupWidth' => 800,
					'showPopupInCenter' => true,
				],
				'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => true,
			],
		],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

?>

<script>
	BX.ready(() => {
		BX.message({
			'CRM_COPILOT_CALL_ASSESSMENT_LIST_NOT_FOUND_MSGVER_1': '<?= GetMessageJS('CRM_COPILOT_CALL_ASSESSMENT_LIST_NOT_FOUND_MSGVER_1') ?>',
			'CRM_TYPE_ITEM_DELETE_NOTIFICATION': '<?= GetMessageJS('CRM_TYPE_ITEM_DELETE_NOTIFICATION') ?>',
			'CRM_TYPE_ITEM_DELETE_CONFIRMATION_TITLE': '<?= GetMessageJS('CRM_TYPE_ITEM_DELETE_CONFIRMATION_TITLE') ?>',
			'CRM_TYPE_ITEM_DELETE_CONFIRMATION_MESSAGE': '<?= GetMessageJS('CRM_TYPE_ITEM_DELETE_CONFIRMATION_MESSAGE') ?>',
			'CRM_COPILOT_CALL_ASSESSMENT_LIST_ITEM_DELETE_ERROR': '<?= GetMessageJS('CRM_COPILOT_CALL_ASSESSMENT_LIST_ITEM_DELETE_ERROR') ?>',
			'CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_ASSESSMENT_AVG_TOOLTIP': '<?= GetMessageJS('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_ASSESSMENT_AVG_TOOLTIP') ?>',
		});

		const grid = new BX.Crm.Copilot.CallAssessmentList.Grid('<?= $arResult['GRID_ID'] ?>');

		grid.init();
	});
</script>
