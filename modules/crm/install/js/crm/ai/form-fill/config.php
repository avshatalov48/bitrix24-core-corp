<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/ai-form-fill.bundle.css',
	'js' => 'dist/ai-form-fill.bundle.js',
	'rel' => [
		'ui.dialogs.messagebox',
		'ui.vue3',
		'ui.vue3.vuex',
		'ui.analytics',
		'ui.notification',
		'crm.ai.feedback',
		'main.core',
		'ui.buttons',
		'crm.ai.call',
		'crm.ai.slider',
	],
	'skip_core' => false,
];
