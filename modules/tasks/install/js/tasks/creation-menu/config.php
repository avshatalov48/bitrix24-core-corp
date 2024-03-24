<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/creation-menu.bundle.css',
	'js' => 'dist/creation-menu.bundle.js',
	'rel' => [
		'main.popup',
		'ui.analytics',
		'main.core',
	],
	'skip_core' => false,
];