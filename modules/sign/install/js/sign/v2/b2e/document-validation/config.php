<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-validation.bundle.css',
	'js' => 'dist/document-validation.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.b2e.representative-selector',
		'sign.v2.api',
		'sign.v2.helper',
	],
	'skip_core' => false,
];