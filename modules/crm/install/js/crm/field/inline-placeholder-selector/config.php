<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/inline-placeholder-selector.bundle.css',
	'js' => 'dist/inline-placeholder-selector.bundle.js',
	'rel' => [
		'main.core',
		'ui.entity-selector',
		'ui.forms',
	],
	'skip_core' => false,
];
