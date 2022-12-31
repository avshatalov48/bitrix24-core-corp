<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$APPLICATION->setTitle(Loc::getMessage('SITEMAP_TITLE'));

$menuItems = $arResult;
$arResult = [
	'MAP_ITEMS' => [],
];

if (!Loader::includeModule('intranet'))
{
	return;
}

\Bitrix\Main\UI\Extension::load(['ui.info-helper']);

$teamWorkIds = array_flip([
	'menu_live_feed',
	'menu_im_messenger',
	'menu_calendar',
	'menu_documents',
	'menu_files',
	'menu_external_mail',
	'menu_all_groups',
]);

$teamworkItems = [];
foreach ($menuItems as $itemIndex => $item)
{
	if (isset($item['PERMISSION']) && $item['PERMISSION'] <= 'D')
	{
		continue;
	}

	$menuId = $item['PARAMS']['menu_item_id'] ?? '';
	if (isset($teamWorkIds[$menuId]))
	{
		$teamworkItems[] = array_merge($item, ['DEPTH_LEVEL' => 2]);

		//Skip empty root items
		if (
			$item['DEPTH_LEVEL'] !== 1
			|| !isset($menuItems[$itemIndex + 1])
			|| $menuItems[$itemIndex + 1]['DEPTH_LEVEL'] !== 1)
		{
			$arResult['MAP_ITEMS'][] = $item;
		}
	}
	else
	{
		$arResult['MAP_ITEMS'][] = $item;
	}
}

if (!empty($teamworkItems))
{
	array_unshift($teamworkItems, [
		'TEXT' => Loc::getMessage('SITEMAP_TEAMWORK'),
		'LINK' => SITE_DIR,
		'SELECTED' => false,
		'PERMISSION' => 'X',
		'PARAMS' => array(
			'menu_item_item' => 'my_instruments'
		),
		'DEPTH_LEVEL' => 1,
		'IS_PARENT' => true,
		'ADDITIONAL_LINKS' => array()
	]);

	$arResult['MAP_ITEMS'] = array_merge($teamworkItems, $arResult['MAP_ITEMS']);
}

