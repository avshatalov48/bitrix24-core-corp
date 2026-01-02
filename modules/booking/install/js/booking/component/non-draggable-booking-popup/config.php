<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/non-draggable-booking-popup.bundle.css',
	'js' => 'dist/non-draggable-booking-popup.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
	],
	'skip_core' => false,
];
