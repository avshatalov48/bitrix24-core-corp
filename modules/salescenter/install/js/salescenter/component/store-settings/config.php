<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/store-settings.bundle.css',
	'js' => 'dist/store-settings.bundle.js',
	'rel' => [
		'ui.vue',
		'rest.client',
		'salescenter.manager',
		'main.popup',
		'main.core',
		'salescenter.component.store-preview',
		'salescenter.component.mycompany-requisite-settings',
		'ui.design-tokens',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];