<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/interface.bundle.js',
	'rel' => [
		'main.core',
		'ui.vue3.vuex',
		'booking.const',
		'booking.lib.timezone',
	],
	'skip_core' => false,
];
