<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/resources-type-service.bundle.css',
	'js' => 'dist/resources-type-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.core',
		'booking.lib.api-client',
		'booking.model.resource-types',
	],
	'skip_core' => true,
];
