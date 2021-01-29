<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/automation-stage.bundle.css',
	'js' => 'dist/automation-stage.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];