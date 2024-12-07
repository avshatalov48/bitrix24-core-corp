<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sign-settings-employee.bundle.css',
	'js' => 'dist/sign-settings-employee.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.b2e.start-process',
		'sign.v2.b2e.submit-document-info',
		'ui.wizard',
		'sign.v2.helper',
	],
	'skip_core' => false,
];