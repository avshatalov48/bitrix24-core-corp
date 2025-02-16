<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/resource-creation-wizard.bundle.css',
	'js' => 'dist/resource-creation-wizard.bundle.js',
	'rel' => [
		'main.core',
		'ui.vue3.vuex',
		'booking.const',
		'booking.model.resources',
		'ui.vue3',
		'booking.core',
	],
	'skip_core' => false,
];
