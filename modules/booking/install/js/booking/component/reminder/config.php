<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/reminder.bundle.css',
	'js' => 'dist/reminder.bundle.js',
	'rel' => [
		'main.popup',
		'main.core',
		'ui.icon-set.api.vue',
		'ui.vue3',
	],
	'skip_core' => false,
];
