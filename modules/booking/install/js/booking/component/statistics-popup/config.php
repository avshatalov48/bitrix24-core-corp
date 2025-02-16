<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/statistics-popup.bundle.css',
	'js' => 'dist/statistics-popup.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.popup',
		'booking.component.button',
		'booking.component.popup',
	],
	'skip_core' => true,
];
