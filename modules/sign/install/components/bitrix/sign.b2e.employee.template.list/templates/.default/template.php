<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Toolbar\ButtonLocation;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */

/** @var $APPLICATION */

$APPLICATION->SetTitle((string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_TITLE') ?? '');

\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
	'GRID_ID' => $arParams['GRID_ID'] ?? '',
	'FILTER_ID' => $arParams['FILTER_ID'] ?? '',
	'FILTER' => $arParams['FILTER_FIELDS'] ?? [],
	'FILTER_ROWS' => $arParams['DEFAULT_FILTER_FIELDS'] ?? [],
	'FILTER_PRESETS' => $arParams['FILTER_PRESETS'] ?? [],
	'DISABLE_SEARCH' => false,
	'ENABLE_LIVE_SEARCH' => true,
	'ENABLE_LABEL' => true,
	'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
]);
\Bitrix\UI\Toolbar\Facade\Toolbar::addButton(new \Bitrix\UI\Buttons\CreateButton([
	'link' => $arParams['ADD_NEW_TEMPLATE_LINK'] ?? '#',
	'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ADD_NEW_TITLE') ?? '',
]),
	ButtonLocation::AFTER_TITLE
);

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arParams['GRID_ID'] ?? '',
		'COLUMNS' => $arParams['COLUMNS'] ?? '',
		'ROWS' => $arResult['TEMPLATES'] ?? [],
		'NAV_OBJECT' => $arResult['PAGE_NAVIGATION'] ?? null,
		'SHOW_ROW_CHECKBOXES' => true,
		'SHOW_TOTAL_COUNTER' => true,
		'TOTAL_ROWS_COUNT' => $arResult['TOTAL_COUNT'] ?? 0,
		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
	]);
?>
