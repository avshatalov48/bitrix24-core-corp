<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/tour.bundle.css',
	'js' => 'dist/tour.bundle.js',
	'rel' => [
		'ui.tour',
		'main.core',
		'main.popup',
	],
	'skip_core' => false,
];