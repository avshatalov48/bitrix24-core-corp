<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/confirm-page-public.bundle.css',
	'js' => 'dist/confirm-page-public.bundle.js',
	'rel' => [
		'ui.vue3',
		'booking.model.bookings',
		'booking.component.mixin.loc-mixin',
		'ui.icon-set.main',
		'main.date',
		'ui.icon-set.api.vue',
		'main.core',
		'main.popup',
		'booking.component.button',
		'booking.component.popup',
		'ui.icon-set.actions',
	],
	'skip_core' => false,
];
