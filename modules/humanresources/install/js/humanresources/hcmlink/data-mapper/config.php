<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'ui.alerts',
		'ui.entity-selector',
		'main.core.events',
		'ui.avatar',
		'ui.icon-set.api.core',
		'main.popup',
		'ui.icon-set.actions',
		'ui.vue3',
		'ui.buttons',
		'humanresources.hcmlink.api',
		'main.core',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];
