<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-summary.bundle.css',
	'js' => 'dist/document-summary.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.api',
		'main.core.events',
	],
	'skip_core' => false,
];