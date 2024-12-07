<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-counter.bundle.css',
	'js' => 'dist/document-counter.bundle.js',
	'rel' => [
		'main.core.events',
		'ui.counterpanel',
		'main.core',
	],
	'skip_core' => false,
];