<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-setup.bundle.css',
	'js' => 'dist/document-setup.bundle.js',
	'rel' => [
		'sign.v2.api',
		'sign.v2.b2e.sign-dropdown',
		'sign.v2.document-setup',
		'sign.v2.helper',
		'sign.v2.sign-settings',
		'main.core',
		'main.date',
	],
	'skip_core' => false,
];