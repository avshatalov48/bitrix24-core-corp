<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/mouse-position.bundle.js',
	'rel' => [
		'main.core',
		'booking.core',
		'booking.const',
	],
	'skip_core' => false,
];
