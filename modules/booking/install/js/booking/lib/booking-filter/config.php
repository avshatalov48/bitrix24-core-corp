<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/booking-filter.bundle.css',
	'js' => 'dist/booking-filter.bundle.js',
	'rel' => [
		'main.core',
		'booking.const',
		'booking.core',
	],
	'skip_core' => false,
];
