<?php

use Bitrix\Intranet\Integration\Templates\Air\TopMenu;
use Bitrix\Intranet\Site\Sections\CollaborationSection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$APPLICATION->setTitle(Loc::getMessage('SITEMAP_TITLE'));

if (!is_array($arResult) || empty($arResult) || !Loader::includeModule('intranet'))
{
	return;
}

$items = TopMenu::convertFromFileMenu($arResult);
$teamWorkIds = array_flip([
	'menu_live_feed',
	'menu_im_messenger',
	'menu_calendar',
	'menu_documents',
	'menu_boards',
	'menu_files',
	'menu_external_mail',
	'menu_all_groups',
]);

$items = array_filter($items, static function ($item) use ($teamWorkIds) {
	return !isset($teamWorkIds[$item['ID']]) ?? false;
});

$collaborationItems = CollaborationSection::getItems();
if (!empty($collaborationItems))
{
	$subMenuData = array_map(function ($item) {
		return [
			'TEXT' => $item['title'],
			'LINK' => $item['url'],
			'PARAMS' => $item['menuData'] ?? [],
			'DEPTH_LEVEL' => 1,
		];
	}, $collaborationItems);

	$teamWorkItem = [
		'TEXT' => Loc::getMessage('SITEMAP_TEAMWORK'),
		'URL' => SITE_DIR,
		'ITEMS' => TopMenu::convertFromFileMenu($subMenuData),
	];

	array_unshift($items, $teamWorkItem);
}

$arResult = ['MAP_ITEMS' => $items];

\Bitrix\Main\UI\Extension::load(['ui.info-helper']);

