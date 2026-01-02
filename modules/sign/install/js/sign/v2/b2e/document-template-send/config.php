<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-template-send.bundle.css',
	'js' => 'dist/document-template-send.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.core.events',
		'ui.vue3',
		'sign.v2.b2e.sign-settings-templates',
		'sign.v2.b2e.vue-util',
		'ui.progressbar',
		'ui.vue3.pinia',
		'ui.vue3.components.popup',
	],
	'skip_core' => true,
];
