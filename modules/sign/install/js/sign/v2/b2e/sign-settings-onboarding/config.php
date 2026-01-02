<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'main.core',
		'main.core.cache',
		'main.loader',
		'sign.v2.b2e.submit-document-info',
		'sign.v2.helper',
		'ui.wizard',
		'sign.v2.api',
	],
	'skip_core' => false,
];