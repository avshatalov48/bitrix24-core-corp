<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/methodology.bundle.css',
	'js' => 'dist/methodology.bundle.js',
	'rel' => [
		'ui.manual',
		'ui.popupcomponentsmaker',
		'main.core',
		'ui.dialogs.messagebox',
		'main.core.events',
		'ui.hint',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];