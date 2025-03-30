<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/remove-booking.bundle.css',
	'js' => 'dist/remove-booking.bundle.js',
	'rel' => [
		'main.core',
		'ui.notification',
		'booking.const',
		'booking.core',
		'booking.provider.service.booking-service',
	],
	'skip_core' => false,
];
