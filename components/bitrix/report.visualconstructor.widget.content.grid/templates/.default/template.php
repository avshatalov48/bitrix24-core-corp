<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.icons',
	'ui.hint',
]);

$this->addExternalCss($this->GetFolder() . '/css/groupingelementstyle.css');
$this->addExternalCss($this->GetFolder() . '/css/element.css');

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'ROWS' => $arResult['ROWS'],
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_GRID_SETTINGS_MENU' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => false,
		'ALLOW_COLUMNS_SORT' => false
	),
	$component,
	array(
		'HIDE_ICONS' => 'Y'
	)
);
