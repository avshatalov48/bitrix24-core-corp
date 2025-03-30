<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/busy-slots.bundle.css',
	'js' => 'dist/busy-slots.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.core',
		'booking.const',
		'booking.lib.duration',
		'booking.provider.service.resource-dialog-service',
		'booking.lib.resources-date-cache',
	],
	'skip_core' => true,
];
