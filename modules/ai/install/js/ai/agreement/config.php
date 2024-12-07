<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/agreement.bundle.css',
	'js' => 'dist/agreement.bundle.js',
	'rel' => [
		'ai.engine',
		'main.popup',
		'ui.buttons',
		'main.core',
		'ui.notification',
	],
	'skip_core' => false,
];