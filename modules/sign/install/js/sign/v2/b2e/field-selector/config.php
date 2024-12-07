<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/selector.bundle.css',
	'js' => 'dist/selector.bundle.js',
	'rel' => [
		'ui.sidepanel.layout',
		'ui.userfieldfactory',
		'ui.buttons',
		'main.core.events',
		'main.core',
	],
	'skip_core' => false,
];