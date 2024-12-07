<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sign-cancellation.bundle.css',
	'js' => 'dist/sign-cancellation.bundle.js',
	'rel' => [
		'ui.notification',
		'main.core',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
];