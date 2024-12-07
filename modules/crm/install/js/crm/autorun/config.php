<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/autorun.bundle.css',
	'js' => 'dist/autorun.bundle.js',
	'rel' => [
		'ui.design-tokens',
		'crm.integration.analytics',
		'ui.analytics',
		'ui.dialogs.messagebox',
		'main.core',
	],
	'skip_core' => false,
];
