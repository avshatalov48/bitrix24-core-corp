<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/in-interval.bundle.css',
	'js' => 'dist/in-interval.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];
