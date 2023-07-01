<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/toolbar.bundle.css',
	'js' => 'dist/toolbar.bundle.js',
	'rel' => [
		'main.popup',
		'main.core',
		'market.rating-store',
		'ui.vue3.pinia',
		'ui.design-tokens',
	],
	'skip_core' => false,
];