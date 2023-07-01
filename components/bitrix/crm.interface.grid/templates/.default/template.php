<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var CBitrixComponent $component Parent component*/

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.grid',
	'',
	[
		'GRID_ID' => $arParams['~GRID_ID'] ?? null,
		'HEADERS' => $arParams['~HEADERS'] ?? null,
		'SORT' => $arParams['~SORT'] ?? null,
		'SORT_VARS' => $arParams['~SORT_VARS'] ?? null,
		'ROWS' => $arParams['~ROWS'] ?? null,
		'FOOTER' => $arParams['~FOOTER'] ?? null,
		'EDITABLE' => $arParams['~EDITABLE'] ?? null,
		'ACTIONS' => $arParams['~ACTIONS'] ?? null,
		'ACTION_ALL_ROWS' => $arParams['~ACTION_ALL_ROWS'] ?? null,
		'NAV_OBJECT' => $arParams['~NAV_OBJECT'] ?? null,
		'FORM_ID' => $arParams['~FORM_ID'] ?? null,
		'TAB_ID' => $arParams['~TAB_ID'] ?? null,
		'CURRENT_URL' => $arParams['~FORM_URI'] ?? '',
		'AJAX_MODE' => $arParams['~AJAX_MODE'] ?? null,
		'AJAX_ID' => $arParams['~AJAX_ID'] ?? '',
		'AJAX_OPTION_JUMP' => $arParams['~AJAX_OPTION_JUMP'] ?? 'N',
		'AJAX_OPTION_HISTORY' => $arParams['~AJAX_OPTION_HISTORY'] ?? 'N',
		'AJAX_LOADER' => $arParams['~AJAX_LOADER'] ?? null,
		'GRID_INIT_EVENT_PARAMS' =>
			is_array($arParams['~GRID_INIT_EVENT_PARAMS'] ?? null)
				? $arParams['~GRID_INIT_EVENT_PARAMS']
				: []
		,
		'FILTER' => $arParams['~FILTER'] ?? null,
		'FILTER_PRESETS' => $arParams['~FILTER_PRESETS'] ?? null,
		'FILTER_TEMPLATE' => $arParams['~FILTER_TEMPLATE'] ?? '',
		'FILTER_NAVIGATION_BAR' => $arParams['~FILTER_NAVIGATION_BAR'] ?? null,
		'IS_EXTERNAL_FILTER' => $arParams['~IS_EXTERNAL_FILTER'] ?? false,
		'RENDER_FILTER_INTO_VIEW' => $arParams['~RENDER_FILTER_INTO_VIEW'] ?? '',
		'HIDE_FILTER' => $arParams['~HIDE_FILTER'] ?? false,
		'MANAGER' => $arParams['~MANAGER'] ?? null

	],
	$component,
	['HIDE_ICONS' => 'Y']
);
?>
