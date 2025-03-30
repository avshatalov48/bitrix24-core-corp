<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'ui.vue3',
		'ui.alerts',
		'ui.entity-selector',
		'ui.avatar',
		'ui.icon-set.api.core',
		'main.popup',
		'ui.icon-set.actions',
		'im.v2.lib.date-formatter',
		'ui.buttons',
		'ui.icon-set.api.vue',
		'humanresources.hcmlink.api',
		'main.core',
		'main.core.events',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];
