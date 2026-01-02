<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/binder.bundle.js',
	'skip_core' => false,
	'rel' => [
		'ui.buttons',
		'ui.entity-selector',
		'ui.notification',
		'main.core',
	],
];
