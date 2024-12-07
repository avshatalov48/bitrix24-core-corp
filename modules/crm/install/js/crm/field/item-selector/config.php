<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/item-selector.bundle.css',
	'js' => 'dist/item-selector.bundle.js',
	'rel' => [
		'main.core.events',
		'main.popup',
		'main.core',
		'ui.buttons',
		'ui.design-tokens',
	],
	'skip_core' => false,
];
