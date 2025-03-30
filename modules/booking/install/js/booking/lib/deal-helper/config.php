<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/deal-helper.bundle.css',
	'js' => 'dist/deal-helper.bundle.js',
	'rel' => [
		'main.core',
		'main.sidepanel',
		'booking.core',
		'booking.const',
		'booking.provider.service.booking-service',
	],
	'skip_core' => false,
];
