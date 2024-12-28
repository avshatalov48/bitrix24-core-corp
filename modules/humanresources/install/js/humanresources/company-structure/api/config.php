<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/api.bundle.css',
	'js' => 'dist/api.bundle.js',
	'rel' => [
		'main.core',
		'ui.notification',
		'ui.analytics',
	],
	'skip_core' => false,
];