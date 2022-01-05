<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/methodology.bundle.css',
	'js' => 'dist/methodology.bundle.js',
	'rel' => [
		'ui.popupcomponentsmaker',
		'main.core',
		'main.core.events',
		'ui.hint',
	],
	'skip_core' => false,
];