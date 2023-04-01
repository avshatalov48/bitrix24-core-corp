<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/info-popup.bundle.css',
	'js' => 'dist/info-popup.bundle.js',
	'rel' => [
		'currency.currency-core',
		'main.core',
		'ui.vue3',
		'main.popup',
	],
	'skip_core' => false,
];