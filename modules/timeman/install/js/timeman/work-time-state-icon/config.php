<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/work-time-state-icon.bundle.css',
	'js' => 'dist/work-time-state-icon.bundle.js',
	'rel' => [
		'main.core',
		'pull.client',
		'main.core.events',
	],
	'skip_core' => false,
];
