<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/dod.bundle.css',
	'js' => 'dist/dod.bundle.js',
	'rel' => [
		'ui.sidepanel.layout',
		'ui.notification',
		'ui.layout-form',
		'ui.forms',
		'ui.entity-selector',
		'main.core',
		'main.core.events',
		'main.loader',
		'ui.dialogs.messagebox',
		'ui.buttons',
	],
	'skip_core' => false,
];