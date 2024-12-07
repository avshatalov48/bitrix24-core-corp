<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/my-reviews-component.bundle.css',
	'js' => 'dist/my-reviews-component.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.popup',
		'market.market-links',
		'ui.vue3',
		'ui.icon-set.actions',
		'ui.forms',
		'ui.buttons',
		'ui.alerts',
		'ui.notification',
	],
	'skip_core' => true,
];