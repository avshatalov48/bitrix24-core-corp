<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/scheme-selector.bundle.css',
	'js' => 'dist/scheme-selector.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.api',
	],
	'skip_core' => false,
];