<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/collection-top.bundle.css',
	'js' => 'dist/collection-top.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'market.rating-store',
		'ui.vue3.pinia',
	],
	'skip_core' => true,
];