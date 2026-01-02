<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/progress-bar.bundle.css',
	'js' => 'dist/progress-bar.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];
