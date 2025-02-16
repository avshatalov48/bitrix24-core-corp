<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/resolvable.bundle.css',
	'js' => 'dist/resolvable.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];
