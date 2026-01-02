<?php

use Bitrix\Booking\Internals\Integration\Calendar\Schedule;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$schedule = null;
if (Loader::includeModule('booking'))
{
	$schedule = Schedule::getRange();
}

return [
	'js' => 'dist/core.bundle.js',
	'rel' => [
		'main.core',
		'ui.vue3.vuex',
		'booking.const',
		'booking.model.bookings',
		'booking.model.message-status',
		'booking.model.clients',
		'booking.model.counters',
		'booking.model.interface',
		'booking.model.resource-types',
		'booking.model.resources',
		'booking.model.favorites',
		'booking.model.dictionary',
		'booking.model.main-resources',
		'booking.model.wait-list',
		'booking.provider.pull.booking-pull-manager',
		'booking.model.filter',
		'booking.model.forms-menu',
	],
	'skip_core' => false,
	'settings' => [
		'schedule' => [
			'fromHour' => $schedule?->getFromAsHours() ?? 9,
			'toHour' => $schedule?->getToAsHours() ?? 19,
		],
	],
];
