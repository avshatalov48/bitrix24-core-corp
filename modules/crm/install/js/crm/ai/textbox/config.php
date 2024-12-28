<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/textbox.bundle.css',
	'js' => 'dist/textbox.bundle.js',
	'rel' => [
		'main.core',
		'ui.icon-set.api.core',
		'ui.design-tokens',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];
