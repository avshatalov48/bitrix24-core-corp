<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/rating.bundle.css',
	'js' => 'dist/rating.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3',
		'market.install-store',
		'main.popup',
		'ui.design-tokens',
		'ui.vue3.pinia',
	],
	'skip_core' => true,
];