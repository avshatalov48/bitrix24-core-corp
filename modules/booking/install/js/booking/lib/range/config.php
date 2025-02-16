<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/range.bundle.css',
	'js' => 'dist/range.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];
