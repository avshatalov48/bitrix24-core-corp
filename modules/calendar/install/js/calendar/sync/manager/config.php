<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/manager.bundle.css',
	'js' => 'dist/manager.bundle.js',
	'rel' => [
		'main.popup',
		'main.core.events',
		'main.core',
		'calendar.util',
	],
	'skip_core' => false,
];