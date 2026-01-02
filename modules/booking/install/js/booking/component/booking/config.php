<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/booking.bundle.css',
	'js' => 'dist/booking.bundle.js',
	'rel' => [
		'booking.component.note-popup',
		'ui.vue3.vuex',
		'booking.component.client-popup',
		'ui.vue3.directives.hint',
		'ui.icon-set.main',
		'ui.icon-set.api.vue',
		'ui.icon-set.crm',
		'booking.const',
		'booking.lib.limit',
		'booking.lib.deal-helper',
		'main.core',
		'main.popup',
		'booking.component.popup',
	],
	'skip_core' => false,
];
