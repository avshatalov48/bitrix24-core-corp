<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die;
}

use Bitrix\Crm\RepeatSale\Widget\WidgetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

$this->getComponent()->addTopPanel($this);
$this->getComponent()->addToolbar($this);

Extension::load([
	'crm.router',
	'crm.repeat-sale.widget',
	'main.core',
	'ui.icons',
	'ui.sidepanel',
	'ui.switcher',
	'ui.design-tokens',
]);

Container::getInstance()->getLocalization()->loadMessages();

/** @var array $arResult */
global $APPLICATION;
$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('CRM_COMMON_REPEAT_SALE'));

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
			'HIDE_FILTER' => true,
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
		],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

?>

<script>
	BX.ready(() => {
		BX.message({
			'CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_CONFIRM_DIALOG_MESSAGE': '<?= GetMessageJS('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_CONFIRM_DIALOG_MESSAGE') ?>',
			'CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_CONFIRM_DIALOG_OK_BTN': '<?= GetMessageJS('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_CONFIRM_DIALOG_OK_BTN') ?>',
			'CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_RS_CONFIRM_DIALOG_MESSAGE': '<?= GetMessageJS('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_RS_CONFIRM_DIALOG_MESSAGE') ?>',
			'CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_RS_CONFIRM_DIALOG_OK_BTN': '<?= GetMessageJS('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_RS_CONFIRM_DIALOG_OK_BTN') ?>',
		});
	});
</script>

<?php
WidgetManager::getInstance()->showBanner();
