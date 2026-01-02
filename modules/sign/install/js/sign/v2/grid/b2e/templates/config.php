<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'sign.v2.analytics',
		'ui.switcher',
		'ui.dialogs.messagebox',
		'main.core',
		'main.core.events',
		'sign.v2.api',
		'sign.feature-storage',
		'sign.type',
	],
	'skip_core' => false,
];
