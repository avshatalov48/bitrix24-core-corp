<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/calendar-data-service.bundle.css',
	'js' => 'dist/calendar-data-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.core',
		'booking.const',
		'booking.lib.api-client',
	],
	'skip_core' => true,
];
