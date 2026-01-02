<?php

use Bitrix\Booking\Entity\Booking\BookingVisitStatus;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\PushPull\PushPullCommandType;
use Bitrix\Booking\Internals\Service\Notifications\NotificationTemplateType;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Loader::includeModule('booking'))
{
	return [];
}

return [
	'js' => 'dist/dictionary-service.bundle.js',
	'rel' => [
		'main.core',
		'booking.core',
		'booking.const',
	],
	'skip_core' => false,
	'settings' => [
		'counters' => CounterDictionary::toArray(),
		'pushCommands' => PushPullCommandType::toArray(),
		'notifications' => NotificationType::toArray(),
		'notificationTemplateTypes' => NotificationTemplateType::toArray(),
		'bookings' => [
			'visitStatuses' => BookingVisitStatus::toArray(),
		],
	],
];
