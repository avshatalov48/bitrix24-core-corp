<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/result.bundle.css',
	'js' => 'dist/result.bundle.js',
	'rel' => [
		'main.core.events',
		'main.core',
	],
	'skip_core' => false,
];