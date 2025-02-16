<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/promo-banner.bundle.css',
	'js' => 'dist/promo-banner.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.vuex',
		'booking.const',
		'ui.icon-set.api.vue',
		'ui.icon-set.main',
		'ui.icon-set.actions',
		'booking.provider.service.main-page-service',
		'booking.component.popup',
		'booking.component.button',
	],
	'skip_core' => true,
];
