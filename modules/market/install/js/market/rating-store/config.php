<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/rating-store.bundle.css',
	'js' => 'dist/rating-store.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.pinia',
	],
	'skip_core' => true,
];