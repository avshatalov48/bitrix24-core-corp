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
	'name' => Loc::getMessage('LANDING_DEMO_23FEB3_TITLE'),
	'description' => Loc::getMessage('LANDING_DEMO_23FEB3_DESCRIPTION'),
	'fields' => array(
		'ADDITIONAL_FIELDS' => array(
			'THEME_CODE' => 'spa',
			'THEME_CODE_TYPO' => 'spa',
			'B24BUTTON_CODE' => $buttons[0],
			'UP_SHOW' => 'Y',
		)
	),
	'items' => array (
	),
	'sort' => \LandingSiteDemoComponent::checkActivePeriod(2,2,2,23) ? 103 : -113,
	'available' => true,
	'active' => \LandingSiteDemoComponent::checkActive(array(
		'ONLY_IN' => array('ru', 'kz', 'by'),
		'EXCEPT' => array()
	))
);