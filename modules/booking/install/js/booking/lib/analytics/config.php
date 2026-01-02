<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/analytics.bundle.js',
	'rel' => [
		'main.core',
		'booking.core',
		'ui.analytics',
		'booking.const',
	],
	'skip_core' => false,
];
