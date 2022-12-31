<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => '/bitrix/js/salescenter/manager/dist/manager.bundle.css',
	'js' => '/bitrix/js/salescenter/manager/dist/manager.bundle.js',
	'rel' => [
		'rest.client',
		'main.core',
		'ui.buttons',
		'clipboard',
		'loadext',
		'popup',
		'sidepanel',
	],
	'skip_core' => false,
];