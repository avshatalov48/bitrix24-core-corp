<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/timeline.bundle.css',
	'js' => 'dist/timeline.bundle.js',
	'rel' => [
		'ui.vue',
		'currency.currency-core',
		'main.core',
	],
	'skip_core' => false,
];