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
		'ui.notification',
		'main.core',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
];