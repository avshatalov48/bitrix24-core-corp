<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/detail.bundle.css',
	'js' => 'dist/detail.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.pinia',
		'ui.vue3',
		'market.detail-component',
	],
	'skip_core' => true,
];