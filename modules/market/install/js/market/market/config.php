<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/market.bundle.css',
	'js' => 'dist/market.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.pinia',
		'ui.vue3',
		'market.toolbar',
		'market.main',
		'market.list-apps',
		'main.core.events',
	],
	'skip_core' => true,
];