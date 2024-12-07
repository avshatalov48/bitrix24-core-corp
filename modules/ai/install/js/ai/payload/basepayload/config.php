<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/basepayload.bundle.css',
	'js' => 'dist/basepayload.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];