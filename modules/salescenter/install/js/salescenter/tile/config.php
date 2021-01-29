<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/tile.bundle.css',
	'js' => 'dist/tile.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];