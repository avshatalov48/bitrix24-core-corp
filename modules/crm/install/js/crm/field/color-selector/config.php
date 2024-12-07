<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/color-selector.bundle.css',
	'js' => 'dist/color-selector.bundle.js',
	'rel' => [
		'main.core',
		'ui.design-tokens',
		'main.core.events',
		'main.popup',
	],
	'skip_core' => false,
];
