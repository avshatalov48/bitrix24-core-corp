<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/crm-form-service.bundle.css',
	'js' => 'dist/crm-form-service.bundle.js',
	'rel' => [
		'main.core',
		'booking.lib.api-client',
		'booking.model.crm-form',
	],
	'skip_core' => false,
];
