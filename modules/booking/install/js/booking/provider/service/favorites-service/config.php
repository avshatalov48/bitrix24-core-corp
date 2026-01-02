<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/favorites-service.bundle.js',
	'rel' => [
		'booking.const',
		'main.core',
		'booking.core',
		'booking.lib.api-client',
	],
	'skip_core' => false,
];
