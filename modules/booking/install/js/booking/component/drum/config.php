<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/drum.bundle.css',
	'js' => 'dist/drum.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.date-picker',
		'booking.component.button',
	],
	'skip_core' => true,
];
