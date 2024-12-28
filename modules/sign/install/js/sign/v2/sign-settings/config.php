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
		'main.core.cache',
		'ui.wizard',
		'sign.v2.preview',
		'sign.v2.analytics',
	],
	'skip_core' => false,
];