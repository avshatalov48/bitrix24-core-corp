<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/resources-service.bundle.css',
	'js' => 'dist/resources-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.core',
		'booking.lib.api-client',
		'booking.const',
	],
	'skip_core' => true,
];
