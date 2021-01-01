<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/scrum.dod.bundle.css',
	'js' => 'dist/scrum.dod.bundle.js',
	'rel' => [
		'main.core.events',
		'main.core',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
];