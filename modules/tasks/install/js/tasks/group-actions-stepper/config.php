<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/group-actions-stepper.bundle.css',
	'js' => 'dist/group-actions-stepper.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.stepprocessing',
	],
	'skip_core' => false,
];