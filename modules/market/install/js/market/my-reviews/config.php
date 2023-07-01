<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/my-reviews.bundle.css',
	'js' => 'dist/my-reviews.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.pinia',
		'ui.vue3',
		'market.my-reviews-component',
	],
	'skip_core' => true,
];