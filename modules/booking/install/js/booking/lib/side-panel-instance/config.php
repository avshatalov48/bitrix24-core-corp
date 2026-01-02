<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/side-panel-instance.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.sidepanel',
	],
	'skip_core' => true,
];
