<?php
/**
 * @global $APPLICATION
 * @global $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$actionPanel =array('GROUPS' => array(array('ITEMS' => array(
	array(
		"TYPE" => \Bitrix\Main\Grid\Panel\Types::CUSTOM,
	),
	array(
		"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
		"TEXT" => GetMessage("CRM_CALL_LIST_DELETE"),
		"VALUE" => "start_call_list",
		"ONCHANGE" => array(
			array(
				"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				"DATA" => array(array('JS' => "BX.CallListActivity.getLast().deleteSelected();"))
			)
		)
	),
))));


$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	array(
		'GRID_ID' => $arResult['CALL_LIST']['ITEMS']['GRID_ID'],
		'COLUMNS' => $arResult['CALL_LIST']['ITEMS']['COLUMNS'],
		'ROWS' => $arResult['CALL_LIST']['ITEMS']['ROWS'],
		'NAV_OBJECT' => $arResult['CALL_LIST']['ITEMS']['NAV_OBJECT'],
		'ALLOW_INLINE_EDIT' => false,
		'SHOW_ROW_ACTIONS_MENU' => $arResult['ALLOW_EDIT'],
		'SHOW_ROW_CHECKBOXES' => $arResult['ALLOW_EDIT'],
		'SHOW_NAVIGATION_PANEL' => true,
		'SHOW_CHECK_ALL_CHECKBOXES' => $arResult['ALLOW_EDIT'],
		'SHOW_GRID_SETTINGS_MENU' => false,
		'SHOW_PAGINATION' => true,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => false,
		'ALLOW_COLUMNS_SORT' => false,
		'ALLOW_COLUMNS_RESIZE' => false,
		'SHOW_ACTION_PANEL' => $arResult['ALLOW_EDIT'],
		'ACTION_PANEL' => $actionPanel,
		'ALLOW_HORIZONTAL_SCROLL' => true,
		'PRESERVE_HISTORY' => 'N'
	),
	$component
);
?>
