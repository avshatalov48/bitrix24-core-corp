<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/toolbar-component.bundle.css',
	'js' => 'dist/toolbar-component.bundle.js',
	'rel' => [
		'main.core.events',
		'ui.buttons',
		'crm.router',
		'main.popup',
		'ui.tour',
		'ui.hint',
		'main.core',
		'ui.navigationpanel',
	],
	'skip_core' => false,
];
