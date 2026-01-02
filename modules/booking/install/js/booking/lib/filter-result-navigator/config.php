<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/filter-result-navigator.bundle.css',
	'js' => 'dist/filter-result-navigator.bundle.js',
	'rel' => [
		'main.core',
		'booking.core',
		'booking.const',
		'booking.lib.remove-resource',
		'booking.provider.service.calendar-service',
	],
	'skip_core' => false,
];
