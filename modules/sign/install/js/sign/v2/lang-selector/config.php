<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/lang-selector.bundle.css',
	'js' => 'dist/lang-selector.bundle.js',
	'rel' => [
		'main.core',
		'ui.buttons',
		'sign.v2.api',
	],
	'skip_core' => false,
];