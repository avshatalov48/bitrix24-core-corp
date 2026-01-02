<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/cycle-popup.bundle.css',
	'js' => 'dist/cycle-popup.bundle.js',
	'rel' => [
		'ui.vue3.components.popup',
		'booking.const',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.icon-set.main',
		'ui.icon-set.outline',
		'booking.component.counter',
		'main.date',
		'main.core',
		'main.popup',
		'ui.vue3',
		'ui.vue3.mixins.loc-mixin',
	],
	'skip_core' => false,
];
