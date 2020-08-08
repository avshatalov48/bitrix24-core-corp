<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/mycompany-requisite-settings.bundle.css',
	'js' => 'dist/mycompany-requisite-settings.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue',
		'salescenter.manager',
	],
	'skip_core' => true,
];