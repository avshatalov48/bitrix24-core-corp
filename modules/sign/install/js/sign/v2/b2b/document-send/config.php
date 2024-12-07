<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-send.bundle.css',
	'js' => 'dist/document-send.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'main.popup',
		'sign.v2.api',
		'sign.v2.helper',
		'sign.v2.lang-selector',
		'sign.v2.document-summary',
	],
	'skip_core' => false,
];