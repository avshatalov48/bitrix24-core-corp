<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/counter-floating.bundle.css',
	'js' => 'dist/counter-floating.bundle.js',
	'rel' => [
		'ui.vue3.vuex',
		'ui.icon-set.api.vue',
		'booking.lib.filter-result-navigator',
		'main.core',
		'booking.const',
		'booking.lib.aha-moments',
		'booking.component.popup',
	],
	'skip_core' => false,
];
