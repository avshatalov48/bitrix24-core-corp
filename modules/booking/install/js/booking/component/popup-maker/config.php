<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/popup-maker.bundle.css',
	'js' => 'dist/popup-maker.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];
