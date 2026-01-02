<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loader::requireModule('crm');

Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'ui.notification',
		'ui.icon-set.api.vue',
		'ui.icon-set.crm',
		'ui.icon-set.outline',
		'ui.dialogs.messagebox',
		'main.core.events',
		'main.popup',
		'ui.buttons',
		'ui.icon-set.api.core',
		'ui.vue3',
		'main.core',
		'crm_common',
		'ui.design-tokens',
	],
	'skip_core' => false,
];
