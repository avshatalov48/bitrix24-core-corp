<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}


return [
	'js' => 'dist/cloud-client-registration.bundle.js',
	'css' => 'dist/cloud-client-registration.bundle.css',
	'rel' => [
		'main.popup',
		'ui.buttons',
		'ui.forms',
		'ui.alerts',
		'ui.layout-form',
		'main.core',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
	'settings' => [],
];
