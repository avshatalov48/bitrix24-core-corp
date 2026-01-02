<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'./dist/hardware.bundle.js',
	],
	'rel' => [
		'main.polyfill.core',
		'main.core.events',
		'im.v2.lib.desktop-api',
	],
	'skip_core' => true,
];