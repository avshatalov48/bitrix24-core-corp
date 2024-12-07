<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/toolbar-component.bundle.css',
	'js' => 'dist/toolbar-component.bundle.js',
	'rel' => [
		'crm.router',
		'main.core.events',
		'main.popup',
		'ui.buttons',
		'ui.tour',
		'ui.hint',
		'main.core',
		'ui.navigationpanel',
	],
	'skip_core' => false,
];
