<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/booking-pull-manager.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'pull.queuemanager',
		'booking.provider.service.counters-service',
		'booking.provider.service.booking-service',
		'booking.provider.service.calendar-service',
		'booking.provider.service.resources-service',
		'booking.provider.service.resources-type-service',
		'booking.core',
		'booking.const',
		'booking.provider.service.client-service',
		'booking.provider.service.main-page-service',
		'booking.provider.service.wait-list-service',
	],
	'skip_core' => false,
];
