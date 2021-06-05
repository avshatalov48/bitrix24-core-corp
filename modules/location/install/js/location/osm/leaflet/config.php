<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/leaflet.bundle.js',
	'css' => 'dist/leaflet.bundle.css',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];