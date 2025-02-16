<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/client-service.bundle.css',
	'js' => 'dist/client-service.bundle.js',
	'rel' => [
		'main.core',
		'booking.const',
		'booking.core',
	],
	'skip_core' => false,
];
