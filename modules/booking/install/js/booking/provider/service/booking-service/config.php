<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/booking-service.bundle.css',
	'js' => 'dist/booking-service.bundle.js',
	'rel' => [
		'main.core',
		'booking.core',
		'booking.const',
		'booking.lib.api-client',
		'booking.lib.booking-filter',
		'booking.provider.service.main-page-service',
		'booking.provider.service.client-service',
		'booking.provider.service.resources-service',
	],
	'skip_core' => false,
];
