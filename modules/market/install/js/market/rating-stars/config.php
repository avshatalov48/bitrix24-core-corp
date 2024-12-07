<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/rating-stars.bundle.css',
	'js' => 'dist/rating-stars.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];