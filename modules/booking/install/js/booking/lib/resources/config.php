<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/resources.bundle.css',
	'js' => 'dist/resources.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.core',
		'booking.const',
		'booking.provider.service.main-page-service',
		'booking.provider.service.favorites-service',
	],
	'skip_core' => true,
];
