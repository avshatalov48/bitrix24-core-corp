<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/booking-event-popup.bundle.css',
	'js' => 'dist/booking-event-popup.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.popup',
		'booking.core',
		'booking.component.mixin.loc-mixin',
		'booking.model.booking-info',
		'main.loader',
		'booking.provider.service.calendar-data-service',
		'ui.vue3.directives.hint',
		'booking.component.avatar',
		'booking.component.button',
		'booking.lib.side-panel-instance',
		'ui.cnt',
		'ui.vue3.components.counter',
		'ui.vue3',
		'ui.vue3.vuex',
		'ui.icon-set.api.vue',
		'ui.icon-set.outline',
		'booking.const',
	],
	'skip_core' => true,
];
