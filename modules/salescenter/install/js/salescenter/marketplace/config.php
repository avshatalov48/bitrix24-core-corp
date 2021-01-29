<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/marketplace.bundle.css',
	'js' => 'dist/marketplace.bundle.js',
	'rel' => [
		'main.core',
		'salescenter.manager',
		'main.core.events',
		'salescenter.tile',
	],
	'skip_core' => false,
];