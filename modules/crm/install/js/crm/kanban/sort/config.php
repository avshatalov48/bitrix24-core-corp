<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sort.bundle.css',
	'js' => 'dist/sort.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];
