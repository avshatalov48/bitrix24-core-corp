<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/apache-superset-embedded-loader.bundle.css',
	'js' => 'dist/apache-superset-embedded-loader.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];
