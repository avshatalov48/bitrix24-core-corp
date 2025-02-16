<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/duration.bundle.css',
	'js' => 'dist/duration.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.date',
	],
	'skip_core' => true,
];
