<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/call-assessment.bundle.css',
	'js' => 'dist/call-assessment.bundle.js',
	'rel' => [
		'ui.vue3',
		'ui.buttons',
		'ui.icon-set.main',
		'ui.text-editor',
		'ui.notification',
		'ui.bbcode.parser',
		'main.core',
		'ui.info-helper',
		'ui.forms',
		'ui.design-tokens',
	],
	'skip_core' => false,
];