<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/collection-item.bundle.css',
	'js' => 'dist/collection-item.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];