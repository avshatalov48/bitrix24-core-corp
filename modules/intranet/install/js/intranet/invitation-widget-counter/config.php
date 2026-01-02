<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/invitation-widget-counter.bundle.css',
	'js' => 'dist/invitation-widget-counter.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.cnt',
	],
	'skip_core' => false,
];
