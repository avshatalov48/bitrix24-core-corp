<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/wait-list-service.bundle.css',
	'js' => 'dist/wait-list-service.bundle.js',
	'rel' => [
		'main.core',
		'booking.core',
		'booking.const',
		'booking.lib.api-client',
		'booking.provider.service.main-page-service',
		'booking.provider.service.client-service',
	],
	'skip_core' => false,
];
