<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/tag.bundle.css',
	'js' => 'dist/tag.bundle.js',
	'rel' => [
		'main.core',
		'ui.sidepanel.layout',
		'main.core.events',
		'ui.notification',
	],
	'skip_core' => false,
];