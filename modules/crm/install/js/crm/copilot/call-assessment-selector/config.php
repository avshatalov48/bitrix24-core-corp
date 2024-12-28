<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/call-assessment-selector.bundle.css',
	'js' => 'dist/call-assessment-selector.bundle.js',
	'rel' => [
		'main.core.events',
		'ui.entity-selector',
		'main.core',
		'ui.icon-set.api.core',
		'ui.design-tokens',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];
