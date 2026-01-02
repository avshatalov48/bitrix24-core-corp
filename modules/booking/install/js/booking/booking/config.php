<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('booking'))
{
	return [];
}

return [
	'lang' => BX_ROOT.'/modules/booking/install/components/bitrix/booking/class.php',
	'css' => 'dist/booking.bundle.css',
	'js' => 'dist/booking.bundle.js',
	'rel' => [
		'booking.component.mixin.loc-mixin',
		'main.loader',
		'booking.component.empty-filter-results-popup',
		'booking.provider.service.main-page-service',
		'booking.provider.service.dictionary-service',
		'booking.provider.service.calendar-service',
		'booking.lib.filter-result-navigator',
		'ui.counterpanel',
		'ui.cnt',
		'main.core.events',
		'booking.lib.drag',
		'ui.vue3.components.rich-loc',
		'ui.notification-manager',
		'booking.provider.service.booking-actions-service',
		'booking.component.loader',
		'ui.vue3.directives.hint',
		'booking.lib.mouse-position',
		'booking.component.time-selector',
		'booking.component.note-popup',
		'ui.icon-set.api.core',
		'ui.icon-set.animated',
		'booking.component.counter',
		'booking.lib.check-booking-intersection',
		'booking.lib.grid',
		'booking.lib.in-interval',
		'booking.lib.range',
		'booking.core',
		'ui.date-picker',
		'booking.component.counter-floating',
		'booking.lib.is-real-id',
		'ui.dialogs.messagebox',
		'booking.component.actions-popup',
		'booking.component.booking',
		'booking.lib.deal-helper',
		'booking.model.bookings',
		'booking.model.clients',
		'booking.provider.service.wait-list-service',
		'booking.lib.remove-booking',
		'booking.lib.remove-wait-list-item',
		'ui.label',
		'booking.lib.currency-format',
		'booking.component.statistics-popup',
		'ui.hint',
		'booking.lib.remove-resource',
		'ui.icon-set.actions',
		'booking.lib.side-panel-instance',
		'booking.resource-creation-wizard',
		'booking.provider.service.resource-dialog-service',
		'booking.lib.resources',
		'booking.lib.resources-date-cache',
		'ui.icon-set.api.vue',
		'ui.icon-set.main',
		'booking.provider.service.option-service',
		'booking.lib.help-desk',
		'booking.lib.busy-slots',
		'ui.entity-selector',
		'booking.lib.limit',
		'booking.provider.service.booking-service',
		'booking.provider.service.client-service',
		'ui.ears',
		'main.date',
		'booking.lib.duration',
		'booking.component.client-popup',
		'booking.lib.analytics',
		'ui.auto-launch',
		'ui.vue3.vuex',
		'main.core',
		'ui.banner-dispatcher',
		'booking.lib.resolvable',
		'booking.lib.aha-moments',
		'main.popup',
		'booking.component.popup',
		'booking.component.help-desk-loc',
		'ui.vue3',
		'booking.const',
		'booking.component.button',
	],
	'skip_core' => false,
	'settings' => [
		'isAirTemplate' =>
			\Bitrix\Main\Loader::includeModule('intranet')
			&& class_exists(\Bitrix\Intranet\Integration\Templates\Air\AirTemplate::class)
			&& \Bitrix\Intranet\Integration\Templates\Air\AirTemplate::isEnabled()
		,
	],
];
