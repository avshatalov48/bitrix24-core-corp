<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'humanresources.hcmlink.api',
		'main.core',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];
