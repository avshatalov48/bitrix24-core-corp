<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/check-booking-intersection.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.lib.in-interval',
	],
	'skip_core' => true,
];
