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
		'market.rating-store',
		'main.popup',
		'ui.vue3.pinia',
	],
	'skip_core' => true,
];