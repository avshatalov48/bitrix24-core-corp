<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/interval-selector.bundle.css',
	'js' => 'dist/interval-selector.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'main.core.events',
	],
	'skip_core' => false,
];