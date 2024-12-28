<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.analytics',
		'sign.v2.api',
		'ui.dialogs.messagebox',
		'ui.switcher',
	],
	'skip_core' => false,
];
