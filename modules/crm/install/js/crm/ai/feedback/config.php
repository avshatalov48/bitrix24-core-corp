<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/feedback.bundle.css',
	'js' => 'dist/feedback.bundle.js',
	'rel' => [
		'crm.integration.analytics',
		'main.core',
		'ui.analytics',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
];
