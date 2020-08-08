<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => [
		'dist/deliveryselector.bundle.css',
	],
	'js' => 'dist/deliveryselector.bundle.js',
	'rel' => [
		'salescenter.manager',
		'ui.vue',
		'main.core',
		'currency',
	],
	'skip_core' => false,
];