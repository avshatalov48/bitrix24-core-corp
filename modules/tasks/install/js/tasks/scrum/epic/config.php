<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/epic.bundle.css',
	'js' => 'dist/epic.bundle.js',
	'rel' => [
		'main.core.events',
		'ui.sidepanel.layout',
		'ui.label',
		'main.core',
	],
	'skip_core' => false,
];