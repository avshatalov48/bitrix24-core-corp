<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/zoom.bundle.css',
	'js' => 'dist/zoom.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'calendar.planner',
		'calendar.util',
	],
	'skip_core' => false,
];
