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
		'sign.feature-storage',
		'sign.v2.analytics',
		'sign.v2.preview',
		'ui.wizard',
	],
	'skip_core' => false,
];