<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/detail-component.bundle.css',
	'js' => 'dist/detail-component.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'market.slider',
		'market.list-item',
		'market.rating',
		'market.popup-install',
		'market.popup-uninstall',
		'market.scope-list',
		'market.install-store',
		'market.uninstall-store',
		'main.core.events',
		'main.popup',
		'ui.design-tokens',
		'ui.vue3.pinia',
	],
	'skip_core' => true,
];