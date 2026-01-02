<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/slot-ranges.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.const',
		'booking.lib.duration',
		'booking.lib.timezone',
	],
	'skip_core' => true,
];
