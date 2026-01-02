<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/toolbar-component.bundle.css',
	'js' => 'dist/toolbar-component.bundle.js',
	'rel' => [
		'crm.client-selector',
		'ui.dialogs.messagebox',
		'ui.navigationpanel',
		'crm.router',
		'pull.client',
		'main.core',
		'main.core.events',
		'main.popup',
		'ui.buttons',
		'ui.tour',
		'ui.hint',
	],
	'skip_core' => false,
];
