<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/dod.bundle.css',
	'js' => 'dist/dod.bundle.js',
	'rel' => [
		'ui.entity-selector',
		'ui.sidepanel.menu',
		'ui.notification',
		'main.core',
		'main.core.events',
		'main.loader',
		'ui.dialogs.messagebox',
		'ui.buttons',
		'ui.sidepanel.layout',
		'ui.layout-form',
		'ui.forms',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];