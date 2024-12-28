<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/entity-selector.bundle.css',
	'js' => 'dist/entity-selector.bundle.js',
	'rel' => [
		'ui.info-helper',
		'main.core.events',
		'main.core',
		'ui.entity-selector',
	],
	'skip_core' => false,
];
