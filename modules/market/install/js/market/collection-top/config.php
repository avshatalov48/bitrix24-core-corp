<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/collection-top.bundle.css',
	'js' => 'dist/collection-top.bundle.js',
	'rel' => [
		'market.rating-stars',
		'market.market-links',
		'main.core',
		'ui.ears',
	],
	'skip_core' => false,
];