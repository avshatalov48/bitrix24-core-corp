<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'ui.notification',
		'ui.dialogs.messagebox',
		'crm.timeline.tools',
		'main.popup',
		'main.core.events',
		'ui.vue3',
		'main.core',
		'crm.router',
		'ui.cnt',
		'ui.label',
		'ui.buttons',
		'ui.vue3.components.audioplayer',
		'ui.alerts',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];
