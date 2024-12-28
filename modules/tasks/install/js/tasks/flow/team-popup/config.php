<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/team-popup.bundle.css',
	'js' => 'dist/team-popup.bundle.js',
	'rel' => [
		'main.core.events',
		'main.loader',
		'main.popup',
		'tasks.side-panel-integration',
		'main.core',
	],
	'skip_core' => false,
];
