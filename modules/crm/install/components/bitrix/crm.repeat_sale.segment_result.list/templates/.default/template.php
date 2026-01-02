<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $arResult array
 */

global $APPLICATION;
$APPLICATION->SetTitle($arResult['TITLE']);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');

$APPLICATION->IncludeComponent(
	'bitrix:crm.deal.list',
	'',
	[
		'INTERNAL_FILTER' => $arResult['FILTER'],
		'EXTENDED_INTERNAL_MODE' => true,
		'ENABLE_TOOLBAR' => false,
		'GRID_ID_SUFFIX' => 'REPEAT_SALE',
		'EXCLUDE_MENU_ITEMS' => ['EXCLUDE'],
		'SHOW_COUNTER_PANEL' => false,
		'SHOW_ROW_CHECKBOXES' => false,
	]
);