<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'script.css',
	'js' => 'script.js',
	'rel' => [
		'main.popup',
		'main.core',
		'main.core.events',
		'tasks.viewed',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];