<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/scrum.dod.bundle.css',
	'js' => 'dist/scrum.dod.bundle.js',
	'rel' => [
		'main.popup',
		'main.loader',
		'ui.dialogs.messagebox',
		'ui.buttons',
		'main.core.events',
		'main.core',
	],
	'skip_core' => false,
];