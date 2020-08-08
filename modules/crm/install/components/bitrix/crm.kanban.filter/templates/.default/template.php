<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Crm\Kanban\Helper;

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');

$filter = Helper::getFilter($arParams['ENTITY_TYPE']);
$presets = Helper::getPresets($arParams['ENTITY_TYPE']);
$grid = Helper::getGrid($arParams['ENTITY_TYPE']);
$gridId = Helper::getGridId($arParams['ENTITY_TYPE']);
$gridFilter = (array)$grid->GetFilter($filter);

if ($arParams['ENTITY_TYPE'] == 'DEAL')
{
	$categoryId = \Bitrix\Crm\Kanban\Helper::getCategoryId();
	$path = '/bitrix/components/bitrix/crm.deal.list/filter.ajax.php'
			. '?filter_id='.urlencode($gridId) . '&category_id=' . $categoryId . '&is_recurring=N&siteID=' . SITE_ID . '&' . bitrix_sessid_get();
	$lasyLoadParams = array(
		'GET_LIST' => $path . '&action=list',
		'GET_FIELD' => $path . '&action=field'
	);
}
else if ($arParams['ENTITY_TYPE'] == 'INVOICE')
{
	$lasyLoadParams = false;
}
else
{
	$path = '/bitrix/components/bitrix/crm.'.mb_strtolower($arParams['ENTITY_TYPE']) . '.list/filter.ajax.php'
			. '?filter_id=' . urlencode($gridId) . '&siteID=' . SITE_ID . '&' . bitrix_sessid_get();
	$lasyLoadParams = array(
		'GET_LIST' => $path . '&action=list',
		'GET_FIELD' => $path . '&action=field'
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.filter',
	'title',
	array(
		'GRID_ID' => $gridId,
		'FILTER_ID' => $gridId,
		'FILTER' => $filter,
		'FILTER_FIELDS' => $gridFilter,
		'FILTER_PRESETS' => $presets,
		'LIMITS' => isset($arResult['LIVE_SEARCH_LIMIT_INFO']) ? $arResult['LIVE_SEARCH_LIMIT_INFO'] : null,
		'ENABLE_LIVE_SEARCH' => true,
		'NAVIGATION_BAR' => $arParams['NAVIGATION_BAR'],
		'LAZY_LOAD' => $lasyLoadParams
	),
	$component,
	array('HIDE_ICONS' => true)
);