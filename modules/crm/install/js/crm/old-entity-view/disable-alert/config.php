<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/disable-alert.bundle.css',
	'js' => 'dist/disable-alert.bundle.js',
	'rel' => [
		'main.core',
		'ui.dialogs.messagebox',
		'ui.notification',
		'ui.buttons',
		'crm.router',
		'ui.design-tokens',
		'ui.design-tokens.air',
	],
	'skip_core' => false,
];
