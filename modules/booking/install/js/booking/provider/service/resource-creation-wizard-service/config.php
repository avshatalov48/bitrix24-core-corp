<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/resource-creation-wizard-service.bundle.css',
	'js' => 'dist/resource-creation-wizard-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.core',
		'booking.lib.api-client',
		'booking.const',
	],
	'skip_core' => true,
];
