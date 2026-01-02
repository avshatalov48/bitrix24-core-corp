<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/regional-settings.bundle.css',
	'js' => 'dist/regional-settings.bundle.js',
	'rel' => [
		'ui.vue3',
		'main.date',
		'sign.v2.api',
		'main.popup',
		'main.core',
		'ui.vue3.components.switcher',
		'ui.switcher',
		'sign.v2.b2e.hcm-link-company-selector',
		'ui.vue3.components.hint',
		'crm.router',
		'sign.v2.b2e.vue-util',
		'main.core.events',
		'ui.vue3.pinia',
	],
	'skip_core' => false,
];
