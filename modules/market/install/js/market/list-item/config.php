<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'./dist/list-item.bundle.js',
	],
	'css' => [
		'./dist/list-item.bundle.css',
	],
	'rel' => [
		'main.polyfill.core',
		'market.popup-install',
		'market.popup-uninstall',
		'ui.vue3.pinia',
		'market.install-store',
		'market.uninstall-store',
		'market.rating-store',
		'ui.icon-set.api.vue',
		'main.popup',
	],
	'skip_core' => true,
];