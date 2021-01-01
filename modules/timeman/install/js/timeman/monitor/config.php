<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/monitor.bundle.css',
	'js' => 'dist/monitor.bundle.js',
	'rel' => [
		'main.core',
		'pull.client',
	],
	'skip_core' => false,
];