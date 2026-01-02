<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/remove-wait-list-item.bundle.css',
	'js' => 'dist/remove-wait-list-item.bundle.js',
	'rel' => [
		'main.core',
		'ui.notification',
		'booking.const',
		'booking.core',
		'booking.provider.service.wait-list-service',
	],
	'skip_core' => false,
];
