<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sign-settings.bundle.css',
	'js' => 'dist/sign-settings.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.b2b.document-send',
		'sign.v2.b2b.requisites',
		'sign.v2.document-setup',
		'sign.v2.sign-settings',
	],
	'skip_core' => false,
];