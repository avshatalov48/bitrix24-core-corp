<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/status.bundle.css',
	'js' => 'dist/status.bundle.js',
	'rel' => [
		'main.date',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.component.elements.hint',
		'main.core',
		'tasks.v2.const',
	],
	'skip_core' => false,
];
