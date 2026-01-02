<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/grid.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.core',
		'booking.const',
		'booking.lib.duration',
	],
	'skip_core' => true,
];
