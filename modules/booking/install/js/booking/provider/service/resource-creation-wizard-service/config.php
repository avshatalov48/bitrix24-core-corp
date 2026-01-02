<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/resource-creation-wizard-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.core',
		'booking.const',
		'booking.lib.api-client',
		'booking.provider.service.option-service',
	],
	'skip_core' => true,
];
