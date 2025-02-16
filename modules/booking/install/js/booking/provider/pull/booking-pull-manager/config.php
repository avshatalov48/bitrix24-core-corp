<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/booking-pull-manager.bundle.css',
	'js' => 'dist/booking-pull-manager.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'pull.queuemanager',
		'booking.provider.service.client-service',
		'booking.provider.service.main-page-service',
		'booking.provider.service.counters-service',
		'booking.provider.service.booking-service',
		'booking.provider.service.calendar-service',
		'booking.provider.service.resources-service',
		'booking.core',
		'booking.const',
		'booking.provider.service.resources-type-service',
	],
	'skip_core' => false,
];
