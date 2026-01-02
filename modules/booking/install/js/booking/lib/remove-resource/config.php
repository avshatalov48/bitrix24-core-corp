<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/remove-resource.bundle.css',
	'js' => 'dist/remove-resource.bundle.js',
	'rel' => [
		'ui.notification',
		'booking.const',
		'booking.core',
		'booking.provider.service.resources-service',
		'main.core',
		'ui.dialogs.messagebox',
		'main.popup',
	],
	'skip_core' => false,
];

