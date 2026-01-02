<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/empty-filter-results-popup.bundle.css',
	'js' => 'dist/empty-filter-results-popup.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'booking.component.popup',
	],
	'skip_core' => true,
];
