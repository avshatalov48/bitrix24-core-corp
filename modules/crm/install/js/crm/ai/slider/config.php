<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

$langAdditional = [];
if (Loader::includeModule('crm'))
{
	$langAdditional['CRM_COMMON_COPILOT'] = Container::getInstance()
		->getLocalization()
		->loadMessages()['CRM_COMMON_COPILOT']
	?? null;
}

return [
	'css' => 'dist/slider.bundle.css',
	'js' => 'dist/slider.bundle.js',
	'rel' => [
		'main.core',
		'ui.buttons',
		'ui.sidepanel',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
	'lang_additional' => $langAdditional,
];
