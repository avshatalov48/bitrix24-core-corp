<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/colorpickertheme.bundle.css',
	'js' => 'dist/colorpickertheme.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];