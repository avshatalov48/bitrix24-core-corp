<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/export.bundle.css',
	'js' => 'dist/export.bundle.js',
	'rel' => [
		'main.popup',
		'main.core',
		'ui.buttons',
	],
	'skip_core' => false,
];