<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/store-settings.bundle.css',
	'js' => 'dist/store-settings.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue',
		'rest.client',
		'salescenter.manager',
		'main.popup',
		'salescenter.component.store-preview',
		'salescenter.component.mycompany-requisite-settings',
	],
	'skip_core' => true,
];