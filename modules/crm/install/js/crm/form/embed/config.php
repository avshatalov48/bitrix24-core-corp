<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/embed.bundle.css',
	'js' => 'dist/embed.bundle.js',
	'rel' => [
		'main.core',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];
