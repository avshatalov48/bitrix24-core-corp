<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

return [
	'name' => Loc::getMessage("LANDING_DEMO_ENT_EN_TITLE"),
	'description' => Loc::getMessage("LANDING_DEMO_ENT_EN_DESCRIPTION"),
	'preview' => '',
	'preview2x' => '',
	'preview3x' => '',
	'preview_url' => '',
	'show_in_list' => 'N',
	'charset' => 'UTF-8',
	'code' => 'ent-en',
	'type' => ['mainpage'],
	'version' => 3,
	'fields' => [
		'ADDITIONAL_FIELDS' => [
			'THEME_USE' => 'Y',
			'THEME_COLOR' => '#1e86ff',
			'BACKGROUND_USE' => 'N',
			'BACKGROUND_POSITION' => 'center',
			'BACKGROUND_COLOR' => '#ffffff',
			'METAOG_TITLE' => Loc::getMessage("LANDING_DEMO_ENT_EN_TITLE"),
			'METAOG_DESCRIPTION' => Loc::getMessage("LANDING_DEMO_ENT_EN_DESCRIPTION"),
			'METAOG_IMAGE' => null,
		],
		'TITLE' => Loc::getMessage("LANDING_DEMO_ENT_EN_TITLE"),
		'LANDING_ID_INDEX' => 'ent-en/main',
		'LANDING_ID_404' => '0',
	],
	'layout' => [],
	'folders' => [],
	'syspages' => [],
	'items' => [
		0 => 'ent-en/main',
	],
];