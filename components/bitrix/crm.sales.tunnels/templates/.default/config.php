<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'script.css',
	'js' => 'script.js',
	'rel' => [
		'main.d3js',
		'main.popup',
		'main.kanban',
		'ui.notification',
		'main.core',
	],
	'skip_core' => false,
];