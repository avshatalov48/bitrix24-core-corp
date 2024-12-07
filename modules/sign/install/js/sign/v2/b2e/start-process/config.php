<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/start-process.bundle.css',
	'js' => 'dist/start-process.bundle.js',
	'rel' => [
		'main.core',
		'main.core.cache',
		'main.loader',
		'sign.v2.api',
		'sign.v2.b2e.sign-dropdown',
	],
	'skip_core' => false,
];