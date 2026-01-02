<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/deal-helper.bundle.js',
	'rel' => [
		'main.core',
		'main.sidepanel',
		'booking.provider.service.booking-service',
		'booking.core',
		'booking.const',
		'booking.provider.service.wait-list-service',
	],
	'skip_core' => false,
];
