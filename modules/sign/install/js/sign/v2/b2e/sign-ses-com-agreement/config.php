<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sign-agreement.bundle.css',
	'js' => 'dist/sign-agreement.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.buttons',
		'sign.v2.api',
	],
	'skip_core' => false,
];