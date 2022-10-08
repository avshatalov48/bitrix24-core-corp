<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'style.css',
	'js' => 'external-link.bundle.js',
	'rel' => [
		'ui.design-tokens',
		'ui.fonts.opensans',
		'clipboard',
		'ui.switcher',
		'ui.layout-form',
		'main.core',
		'main.core.events',
		'main.date',
		'ui.buttons',
		'main.popup',
	],
	'skip_core' => false,
];
