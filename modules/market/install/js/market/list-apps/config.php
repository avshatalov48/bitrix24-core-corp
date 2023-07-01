<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/list-apps.bundle.css',
	'js' => 'dist/list-apps.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3',
		'market.list-item',
		'market.categories',
		'market.install-store',
		'ui.vue3.pinia',
		'main.core.events',
	],
	'skip_core' => true,
];