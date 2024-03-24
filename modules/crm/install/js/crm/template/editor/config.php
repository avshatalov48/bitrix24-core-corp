<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/editor.bundle.css',
	'js' => 'dist/editor.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'crm.entity-selector',
		'ui.entity-selector',
		'ui.design-tokens',
	],
	'skip_core' => false,
];
