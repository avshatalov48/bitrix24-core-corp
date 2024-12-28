<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-party-popup.bundle.css',
	'js' => 'dist/user-party-popup.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'sign.v2.api',
		'main.loader',
		'main.polyfill.intersectionobserver',
	],
	'skip_core' => false,
];
