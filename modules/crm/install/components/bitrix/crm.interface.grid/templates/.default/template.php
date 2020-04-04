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
		'FORM_ID' => $arParams['~FORM_ID'],
		'TAB_ID' => $arParams['~TAB_ID'],
		'CURRENT_URL' => isset($arParams['~FORM_URI']) ? $arParams['~FORM_URI'] : '',
		'AJAX_MODE' => $arParams['~AJAX_MODE'],
		'AJAX_ID' => isset($arParams['~AJAX_ID']) ? $arParams['~AJAX_ID'] : '',
		'AJAX_OPTION_JUMP' => isset($arParams['~AJAX_OPTION_JUMP']) ? $arParams['~AJAX_OPTION_JUMP'] : 'N',
		'AJAX_OPTION_HISTORY' => isset($arParams['~AJAX_OPTION_HISTORY']) ? $arParams['~AJAX_OPTION_HISTORY'] : 'N',
		'AJAX_LOADER' => isset($arParams['~AJAX_LOADER']) ? $arParams['~AJAX_LOADER'] : null,
		'GRID_INIT_EVENT_PARAMS' => is_array($arParams['~GRID_INIT_EVENT_PARAMS']) ?
			$arParams['~GRID_INIT_EVENT_PARAMS'] : array(),
		'FILTER' => $arParams['~FILTER'],
		'FILTER_PRESETS' => $arParams['~FILTER_PRESETS'],
		'FILTER_TEMPLATE' => isset($arParams['~FILTER_TEMPLATE']) ? $arParams['~FILTER_TEMPLATE'] : '',
		'FILTER_NAVIGATION_BAR' => isset($arParams['~FILTER_NAVIGATION_BAR']) ? $arParams['~FILTER_NAVIGATION_BAR'] : null,
		'IS_EXTERNAL_FILTER' => isset($arParams['~IS_EXTERNAL_FILTER']) ? $arParams['~IS_EXTERNAL_FILTER'] : false,
		'RENDER_FILTER_INTO_VIEW' => isset($arParams['~RENDER_FILTER_INTO_VIEW']) ? $arParams['~RENDER_FILTER_INTO_VIEW'] : '',
		'HIDE_FILTER' => isset($arParams['~HIDE_FILTER']) ? $arParams['~HIDE_FILTER'] : false,
		'MANAGER' => isset($arParams['~MANAGER']) ? $arParams['~MANAGER'] : null

	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>
