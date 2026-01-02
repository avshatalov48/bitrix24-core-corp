<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/theme-picker.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'main.loader',
		'main.sidepanel',
	],
	'skip_core' => false,
];
