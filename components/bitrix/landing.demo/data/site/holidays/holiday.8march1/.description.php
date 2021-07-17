<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
Loc::loadLanguageFile(__FILE__);


return array(
	'name' => Loc::getMessage('LANDING_DEMO_8MARCH1_TITLE'),
	'description' => Loc::getMessage('LANDING_DEMO_8MARCH1_DESCRIPTION'),
	'fields' => array(
		'ADDITIONAL_FIELDS' => array(
			'UP_SHOW' => 'Y',
			'THEME_CODE' => 'wedding',
			'THEMEFONTS_CODE' => 'g-font-montserrat',
			'THEMEFONTS_CODE_H' => 'g-font-montserrat',
			'THEMEFONTS_SIZE' => '1',
			'THEMEFONTS_USE' => 'Y',
		)
	),
	'items' => array (
	),
	'available' => true,
	'active' => \LandingSiteDemoComponent::checkActive(array(
		'ONLY_IN' => array('ru', 'kz', 'by', 'ua'),
		'EXCEPT' => array()
	))
);