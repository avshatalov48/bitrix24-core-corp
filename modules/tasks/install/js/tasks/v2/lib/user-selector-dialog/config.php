<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/user-selector-dialog.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.core.events',
		'tasks.v2.lib.entity-selector-dialog',
	],
	'skip_core' => true,
];
