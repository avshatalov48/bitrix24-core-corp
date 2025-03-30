<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/hcm-link-party-checker.bundle.css',
	'js' => 'dist/hcm-link-party-checker.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'main.loader',
		'sign.v2.b2e.hcm-link-mapping',
		'sign.v2.b2e.hcm-link-employee-selector',
	],
	'skip_core' => false,
];
