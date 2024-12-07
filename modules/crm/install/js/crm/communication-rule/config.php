<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/communication-rule.bundle.css',
	'js' => 'dist/communication-rule.bundle.js',
	'rel' => [
		'main.core',
		'ui.vue3',
		'ui.layout-form',
		'ui.entity-selector',
	],
	'skip_core' => false,
];
