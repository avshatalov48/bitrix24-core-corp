<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/ai-form-fill.bundle.css',
	'js' => 'dist/ai-form-fill.bundle.js',
	'rel' => [
		'crm.ai.call',
		'crm.ai.slider',
		'ui.dialogs.messagebox',
		'ui.vue3',
		'ui.vue3.vuex',
		'crm.ai.feedback',
		'crm.integration.analytics',
		'ui.analytics',
		'ui.notification',
		'main.core',
		'ui.buttons',
	],
	'skip_core' => false,
];
