<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/tour-manager.bundle.css',
	'js' => 'dist/tour-manager.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.tour',
		'crm.integration.ui.banner-dispatcher',
	],
	'skip_core' => true,
];
