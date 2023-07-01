<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/collections.bundle.css',
	'js' => 'dist/collections.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'market.collection-item-ads',
		'market.collection-item',
		'market.collection-top',
		'market.collection-top2',
	],
	'skip_core' => true,
];