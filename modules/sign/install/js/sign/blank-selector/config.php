<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/blank-selector.bundle.css',
	'js' => 'dist/blank-selector.bundle.js',
	'rel' => [
		'ui.sidepanel.layout',
		'ui.uploader.core',
		'ui.buttons',
		'sidepanel',
		'main.loader',
		'main.core',
		'main.core.events',
		'ui.entity-selector',
	],
	'skip_core' => false,
];