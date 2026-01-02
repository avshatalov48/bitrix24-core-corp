<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/registration.bundle.css',
	'js' => 'dist/registration.bundle.js',
	'rel' => [
		'ui.icon-set',
		'main.core',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
];
