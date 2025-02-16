<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/aha-moments.bundle.css',
	'js' => 'dist/aha-moments.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'spotlight',
		'ui.tour',
		'ui.auto-launch',
		'ui.banner-dispatcher',
		'booking.core',
		'booking.const',
		'booking.provider.service.option-service',
	],
	'skip_core' => false,
];
