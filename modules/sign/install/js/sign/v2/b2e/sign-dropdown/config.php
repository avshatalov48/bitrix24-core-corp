<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sign-dropdown.bundle.css',
	'js' => 'dist/sign-dropdown.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.entity-selector',
	],
	'skip_core' => false,
];