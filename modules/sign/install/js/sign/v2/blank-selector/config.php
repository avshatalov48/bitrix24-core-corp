<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/blank-selector.bundle.css',
	'js' => 'dist/blank-selector.bundle.js',
	'rel' => [
		'main.date',
		'main.popup',
		'sign.v2.sign-settings',
		'ui.sidepanel.layout',
		'ui.uploader.tile-widget',
		'ui.uploader.core',
		'main.loader',
		'ui.icons',
		'main.core',
		'main.core.events',
		'sign.v2.api',
		'ui.entity-selector',
	],
	'skip_core' => false,
];
