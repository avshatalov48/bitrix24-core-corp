<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/wizard.bundle.css',
	'js' => 'dist/wizard.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.buttons',
		'main.polyfill.intersectionobserver',
		'ui.icon-set.main',
	],
	'skip_core' => false,
];