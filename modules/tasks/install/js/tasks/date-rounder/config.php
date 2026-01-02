<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/date-rounder.bundle.css',
	'js' => 'dist/date-rounder.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];
