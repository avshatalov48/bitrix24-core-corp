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
		'pull.queuemanager',
		'ui.buttons',
		'ui.bbcode.parser',
		'ui.text-editor',
		'ui.icon-set.main',
		'ui.notification',
		'main.core.events',
		'ui.entity-selector',
		'main.core',
		'ui.info-helper',
		'ui.forms',
		'ui.design-tokens',
	],
	'skip_core' => false,
];