<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/main.bundle.css',
	'js' => 'dist/main.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'market.slider',
		'market.collections',
		'market.categories',
	],
	'skip_core' => true,
];