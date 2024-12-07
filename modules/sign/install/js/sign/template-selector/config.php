<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/template-selector.bundle.css',
	'js' => 'dist/template-selector.bundle.js',
	'rel' => [
		'ui.sidepanel.layout',
		'ui.uploader.core',
		'sidepanel',
		'main.core.events',
		'main.core',
		'main.loader',
	],
	'skip_core' => false,
];