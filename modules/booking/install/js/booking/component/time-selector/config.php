<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/time-selector.bundle.css',
	'js' => 'dist/time-selector.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.vue3.vuex',
		'booking.lib.duration',
		'booking.const',
		'main.date',
	],
	'skip_core' => false,
];
