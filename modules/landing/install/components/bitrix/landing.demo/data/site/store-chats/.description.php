<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);


return array(
	'code' => 'store-chats',
	'name' => Loc::getMessage("LANDING_DEMO_STORE_CHATS--NAME"),
	'description' => Loc::getMessage("LANDING_DEMO_STORE_CHATS--DESC"),
	'active' => true,
	'singleton' => true,
	'preview' => '',
	'preview2x' => '',
	'preview3x' => '',
	'preview_url' => '',
	'show_in_list' => 'Y',
	'type' => 'store',
	'sort' => 10,
	'lock_delete' => true,
	'fields' => array(
		'ADDITIONAL_FIELDS' => array(
			'VIEW_USE' => 'N',
			'VIEW_TYPE' => 'no',
			'UP_SHOW' => 'Y',
			'THEME_CODE' => '3corporate',
			'THEMEFONTS_CODE' => 'Roboto',
			'THEMEFONTS_CODE_H' => 'Roboto',
			'THEMEFONTS_SIZE' => '1',
			'THEMEFONTS_USE' => 'Y',
		),
		'TITLE' => Loc::getMessage("LANDING_DEMO_STORE_CHATS--NAME"),
	),
	
	'site_group' => 'Y',
	'site_group_items' => [
		0 => [
			'code' => 'store-chats-dark',
			'page' => 'mainpage',
			'color' => '#000000',
		],
		1 => [
			'code' => 'store-chats-light',
			'page' => 'mainpage',
			'color' => '#F6F6F9',
		],
	],
);