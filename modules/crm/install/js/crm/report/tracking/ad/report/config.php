<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/report.bundle.css',
	'js' => 'dist/report.bundle.js',
	'rel' => [
		'sidepanel',
		'ui.progressbar',
		'main.core',
		'main.core.events',
		'main.popup',
	],
	'skip_core' => false,
];