<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/hcm-link-employee-selector.bundle.css',
	'js' => 'dist/hcm-link-employee-selector.bundle.js',
	'rel' => [
		'main.core.events',
		'sign.v2.api',
		'main.core',
		'ui.select',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];
