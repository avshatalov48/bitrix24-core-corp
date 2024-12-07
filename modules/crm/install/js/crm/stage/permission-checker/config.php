<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/permission-checker.bundle.css',
	'js' => 'dist/permission-checker.bundle.js',
	'rel' => [
		'main.core',
		'ui.notification',
		'crm.stage-model',
	],
	'skip_core' => false,
];