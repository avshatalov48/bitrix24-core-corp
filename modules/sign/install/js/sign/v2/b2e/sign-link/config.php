<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sign-link.bundle.css',
	'js' => 'dist/sign-link.bundle.js',
	'rel' => [
		'ui.buttons',
		'ui.sidepanel-content',
		'ui.design-tokens',
		'main.date',
		'main.core',
		'sign.v2.api',
	],
	'skip_core' => false,
];