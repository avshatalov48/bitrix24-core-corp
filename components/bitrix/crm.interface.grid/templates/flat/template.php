<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.grid',
	'',
	array(
		'GRID_ID' => $arParams['~GRID_ID'],
		'HEADERS' => $arParams['~HEADERS'],
		'SORT' => $arParams['~SORT'],
		'SORT_VARS' => $arParams['~SORT_VARS'],
		'ROWS' => $arParams['~ROWS'],
		'FOOTER' => $arParams['~FOOTER'],
		'EDITABLE' => $arParams['~EDITABLE'],
		'ACTIONS' => $arParams['~ACTIONS'],
		'ACTION_ALL_ROWS' => $arParams['~ACTION_ALL_ROWS'],
		'NAV_OBJECT' => $arParams['~NAV_OBJECT'],
		'FORM_ID' => $arParams['~FORM_ID'] ?? null,
		'TAB_ID' => $arParams['~TAB_ID'] ?? null,
		'AJAX_MODE' => $arParams['~AJAX_MODE'],
		'AJAX_ID' => $arParams['~AJAX_ID'] ?? '',
		'AJAX_OPTION_JUMP' => $arParams['~AJAX_OPTION_JUMP'] ?? 'N',
		'AJAX_OPTION_HISTORY' => $arParams['~AJAX_OPTION_HISTORY'] ?? 'N',
		'AJAX_INIT_EVENT' => $arParams['~AJAX_INIT_EVENT'] ?? '',
		'GRID_INIT_EVENT_PARAMS' => is_array($arParams['~GRID_INIT_EVENT_PARAMS'] ?? null) ?
			$arParams['~GRID_INIT_EVENT_PARAMS'] : array(),
		'FILTER' => $arParams['~FILTER'] ?? null,
		'FILTER_PRESETS' => $arParams['~FILTER_PRESETS'] ?? null,
		'RENDER_FILTER_INTO_VIEW' => $arParams['~RENDER_FILTER_INTO_VIEW'] ?? '',
		'HIDE_FILTER' => $arParams['~HIDE_FILTER'] ?? false,
		'FILTER_TEMPLATE' => $arParams['~FILTER_TEMPLATE'] ?? '',
		'MANAGER' => $arParams['~MANAGER'] ?? null,
		'CUSTOM_EDITABLE_COLUMNS' => $arParams['~CUSTOM_EDITABLE_COLUMNS'] ?? null

	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>
