<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'script.css',
	'js' => 'script.js',
	'rel' => [
		'ui.counterpanel',
		'main.core',
		'main.core.events',
		'tasks.viewed',
		'ui.analytics',
	],
	'skip_core' => false,
];
