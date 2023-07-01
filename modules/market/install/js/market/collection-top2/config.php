<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/collection-top2.bundle.css',
	'js' => 'dist/collection-top2.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'market.collection-top2-list',
	],
	'skip_core' => true,
];