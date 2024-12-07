<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/profile-field-selector.bundle.css',
	'js' => 'dist/profile-field-selector.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];