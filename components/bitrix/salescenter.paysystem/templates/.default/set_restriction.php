<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
?>
<div class="salescenter-restriction-title ui-p-15 ui-bg-color-white">
	<span class="salescenter-restriction-text"><?=Loc::getMessage('SALESCENTER_SP_PSR_TITLE')?></span>
</div>
<div id="salescenter-paysystem-restriction-grid-block">
<?
	$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
		'GRID_ID' => $arParams['GRID_ID'],
		'COLUMNS' => [
			[
				'id' => 'RESTRICTION_ID',
				'name' => Loc::getMessage('SALESCENTER_SP_GRID_HEADER_ID'),
				'sort' => 'ID',
				'default' => true,
			],
			[
				'id' => 'SORT',
				'name' => Loc::getMessage('SALESCENTER_SP_GRID_HEADER_SORT'),
				'sort' => 'SORT',
				'default' => true,
			],
			[
				'id' => 'CLASS_NAME',
				'name' => Loc::getMessage('SALESCENTER_SP_GRID_HEADER_CLASS_NAME'),
				'default' => true,
			],
			[
				'id' => 'PARAMS',
				'name' => Loc::getMessage('SALESCENTER_SP_GRID_HEADER_PARAMS'),
				'default' => true,
			],
		],
		'ROWS' => $arResult['RESTRICTIONS']['ITEMS'],
		'SHOW_ROW_CHECKBOXES' => true,
		'NAV_OBJECT' => $arResult["NAV_OBJECT"],
		'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
		'AJAX_MODE' => 'N',
		'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
		'PAGE_SIZES' => [
			['NAME' => "5", 'VALUE' => '5'],
			['NAME' => '10', 'VALUE' => '10'],
			['NAME' => '20', 'VALUE' => '20'],
		],
		'AJAX_OPTION_JUMP' => 'N',
		'SHOW_CHECK_ALL_CHECKBOXES' => true,
		'SHOW_ROW_ACTIONS_MENU' => true,
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_NAVIGATION_PANEL' => true,
		'SHOW_PAGINATION' => true,
		'SHOW_SELECTED_COUNTER' => true,
		'SHOW_TOTAL_COUNTER' => true,
		'SHOW_PAGESIZE' => true,
		'SHOW_ACTION_PANEL' => true,
		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		'ALLOW_HORIZONTAL_SCROLL' => true,
		'ALLOW_SORT' => true,
		'ALLOW_PIN_HEADER' => true,
		'AJAX_OPTION_HISTORY' => 'N'
	]);
?>
</div>