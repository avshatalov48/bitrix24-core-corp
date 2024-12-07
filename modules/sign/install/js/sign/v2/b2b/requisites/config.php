<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/requisites.bundle.css',
	'js' => 'dist/requisites.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'main.loader',
		'sign.v2.api',
		'sign.v2.helper',
	],
	'skip_core' => false,
];