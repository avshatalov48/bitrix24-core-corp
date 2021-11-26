<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/lib.bundle.css',
	'js' => 'dist/lib.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'salescenter.manager',
	],
	'skip_core' => true,
];