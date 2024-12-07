<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sign-settings-factory.bundle.css',
	'js' => 'dist/sign-settings-factory.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'sign.v2.b2b.sign-settings',
		'sign.v2.b2e.sign-settings',
	],
	'skip_core' => true,
];