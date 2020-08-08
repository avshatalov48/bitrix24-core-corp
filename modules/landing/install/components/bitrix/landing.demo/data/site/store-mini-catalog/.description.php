<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

$buttons = \Bitrix\Landing\Hook\Page\B24button::getButtons();
$buttons = array_keys($buttons);

return array(
	'code' => 'store-mini-catalog',
	'name' => Loc::getMessage('LANDING_DEMO_STORE_MINI_CATALOG_SITE_TXT_1'),
	'description' => Loc::getMessage('LANDING_DEMO_STORE_MINI_CATALOG_SITE_DESC'),
	'preview' => '',
	'preview2x' => '',
	'preview3x' => '',
	'preview_url' => '',
	'show_in_list' => 'Y',
	'type' => 'store',
	'sort' => 30,
	'fields' =>array(
			'ADDITIONAL_FIELDS' =>array(
					'B24BUTTON_CODE' => $buttons[0],
					'VIEW_USE' => 'N',
					'VIEW_TYPE' => 'no',
					'UP_SHOW' => 'Y',
					'THEME_CODE' => 'event',
					'THEMEFONTS_CODE' => 'g-font-open-sans',
					'THEMEFONTS_CODE_H' => 'g-font-cormorant-infant',
					'THEMEFONTS_SIZE' => '1.14286',
					'THEMEFONTS_USE' => 'Y',
				),
		),
	'layout' => array(),
	'folders' =>array(),
	'syspages' =>array(
			'order' => 'store-mini-catalog/buying',
			'cart' => 'store-mini-catalog/buying',
			'payment' => 'store-mini-catalog/payment',
		),
	'items' =>array(
			0 => 'store-mini-catalog/handmade',
			1 => 'store-mini-catalog/buying',
			2 => 'store-mini-catalog/payment',
		),
);