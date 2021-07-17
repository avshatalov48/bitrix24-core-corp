<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/disk.sharing-legacy-popup.bundle.js',
	'rel' => [
		'main.core',
		'ui.buttons',
		'disk',
		'socnetlogdest',
	],
	'skip_core' => false,
];