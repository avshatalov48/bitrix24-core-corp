<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sprint.start.form.bundle.css',
	'js' => 'dist/sprint.start.form.bundle.js',
	'rel' => [
		'main.core.events',
		'ui.sidepanel.layout',
		'main.core',
		'ui.dialogs.messagebox',
		'ui.hint',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];