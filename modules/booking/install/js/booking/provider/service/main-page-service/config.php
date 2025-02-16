<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/main-page-service.bundle.css',
	'js' => 'dist/main-page-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.core',
		'booking.lib.resources-date-cache',
		'booking.lib.api-client',
		'booking.const',
		'booking.provider.service.booking-service',
		'booking.provider.service.client-service',
		'booking.provider.service.resources-service',
		'booking.provider.service.resources-type-service',
	],
	'skip_core' => true,
];
