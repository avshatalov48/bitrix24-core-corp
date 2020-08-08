<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/item.bundle.css',
	'js' => 'dist/item.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];