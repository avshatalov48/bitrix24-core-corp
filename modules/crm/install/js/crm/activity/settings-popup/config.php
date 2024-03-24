<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/settings-popup.bundle.css',
	'js' => 'dist/settings-popup.bundle.js',
	'rel' => [
		'main.popup',
		'ui.buttons',
		'ui.notification',
		'calendar.planner',
		'crm.activity.settings-popup',
		'crm.timeline.tools',
		'main.core',
		'main.date',
		'ui.vue3',
		'ui.design-tokens',
	],
	'skip_core' => false,
];
