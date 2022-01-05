<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/meetings.bundle.css',
	'js' => 'dist/meetings.bundle.js',
	'rel' => [
		'main.loader',
		'main.popup',
		'ui.popupcomponentsmaker',
		'main.core.events',
		'main.core',
		'ui.hint',
		'ui.icons.b24',
		'ui.icons.service',
	],
	'skip_core' => false,
];