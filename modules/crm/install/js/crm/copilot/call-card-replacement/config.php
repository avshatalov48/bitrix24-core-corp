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
		'ui.vue3',
		'crm.router',
		'ui.buttons',
		'ui.bbcode.formatter.html-formatter',
		'crm.copilot.call-assessment-selector',
		'pull.client',
		'ui.vue',
		'ui.vue3.vuex',
		'im.v2.lib.phone',
		'im.v2.lib.desktop-api',
		'main.core',
	],
	'skip_core' => false,
];
