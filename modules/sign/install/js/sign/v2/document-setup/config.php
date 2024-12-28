<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-setup.bundle.css',
	'js' => 'dist/document-setup.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'sign.v2.blank-selector',
		'sign.v2.api',
		'sign.v2.sign-settings',
		'ui.buttons',
		'ui.alerts',
	],
	'skip_core' => false,
];