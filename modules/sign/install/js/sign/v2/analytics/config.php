<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/analytics.bundle.css',
	'js' => 'dist/analytics.bundle.js',
	'rel' => [
		'main.core',
		'sign.type',
		'sign.v2.api',
		'ui.analytics',
	],
	'skip_core' => false,
];
