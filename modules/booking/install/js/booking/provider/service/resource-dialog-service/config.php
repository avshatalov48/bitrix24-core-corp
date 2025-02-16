<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/resource-dialog-service.bundle.css',
	'js' => 'dist/resource-dialog-service.bundle.js',
	'rel' => [
		'main.core',
		'booking.core',
		'booking.const',
		'booking.lib.resources-date-cache',
		'booking.lib.api-client',
		'booking.provider.service.booking-service',
		'booking.provider.service.client-service',
		'booking.provider.service.resources-service',
	],
	'skip_core' => false,
];
