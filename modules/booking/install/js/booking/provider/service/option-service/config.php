<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/option-service.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.lib.api-client',
	],
	'skip_core' => true,
];
