<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => '/bitrix/js/rpa/fieldscontroller/src/fieldscontroller.css',
	'js' => '/bitrix/js/rpa/fieldscontroller/dist/fieldscontroller.bundle.js',
	'rel' => [
		'main.core',
		'ui.userfieldfactory',
		'ui.userfield',
		'main.loader',
		'main.core.events',
		'main.popup',
		'rpa.manager',
		'ui.design-tokens',
		'ui.switcher',
	],
	'skip_core' => false,
];