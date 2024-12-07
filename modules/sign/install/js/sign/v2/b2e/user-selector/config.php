<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-selector.bundle.css',
	'js' => 'dist/user-selector.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.core.events',
		'ui.entity-selector',
	],
	'skip_core' => true,
];