<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/collection-top2-list.bundle.css',
	'js' => 'dist/collection-top2-list.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];