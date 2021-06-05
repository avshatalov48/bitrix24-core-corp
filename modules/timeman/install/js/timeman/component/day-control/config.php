<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/day-control.bundle.css',
	'js' => 'dist/day-control.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue',
		'timeman.const',
		'pull.client',
	],
	'skip_core' => true,
];