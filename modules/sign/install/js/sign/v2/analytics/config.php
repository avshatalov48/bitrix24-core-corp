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
		'sign.v2.api',
		'sign.v2.b2e.company-selector',
		'ui.analytics',
	],
	'skip_core' => false,
];
