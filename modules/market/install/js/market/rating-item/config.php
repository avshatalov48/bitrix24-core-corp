<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/rating-item.bundle.css',
	'js' => 'dist/rating-item.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'market.rating-store',
		'ui.vue3.pinia',
	],
	'skip_core' => true,
];